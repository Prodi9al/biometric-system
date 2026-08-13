terraform {
  required_version = ">= 1.5.0"
  
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.0"
    }
    tls = {
      source  = "hashicorp/tls"
      version = "~> 4.0"
    }
  }
  
  backend "s3" {
    bucket         = "biometric-atm-terraform-state"
    key            = "prod/terraform.tfstate"
    region         = "us-east-1"
    encrypt        = true
    dynamodb_table = "terraform-locks"
  }
}

provider "aws" {
  region = var.aws_region
  
  default_tags {
    tags = {
      Project     = "biometric-atm"
      Environment = var.environment
      ManagedBy   = "terraform"
      Owner       = "platform-team"
    }
  }
}

# Random suffix for unique resource names
resource "random_id" "suffix" {
  byte_length = 4
}

# Local values
locals {
  name_prefix = "${var.project_name}-${var.environment}"
  common_tags = {
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}

# ============================================================================
# MODULES
# ============================================================================

module "vpc" {
  source = "./modules/vpc"
  
  name_prefix          = local.name_prefix
  vpc_cidr             = var.vpc_cidr
  public_subnet_cidrs  = var.public_subnet_cidrs
  private_subnet_cidrs = var.private_subnet_cidrs
  database_subnet_cidrs = var.database_subnet_cidrs
  availability_zones   = var.availability_zones
  tags                 = local.common_tags
}

module "secrets" {
  source = "./modules/secrets"
  
  name_prefix = local.name_prefix
  tags        = local.common_tags
}

module "rds" {
  source = "./modules/rds"
  
  name_prefix            = local.name_prefix
  vpc_id                 = module.vpc.vpc_id
  database_subnet_ids    = module.vpc.database_subnet_ids
  security_group_ids     = [module.vpc.database_sg_id]
  engine                 = "mysql"
  engine_version         = "8.0"
  instance_class         = var.rds_instance_class
  allocated_storage      = var.rds_allocated_storage
  max_allocated_storage  = var.rds_max_allocated_storage
  db_name                = var.db_name
  username               = var.db_username
  password               = var.db_password
  backup_retention_period = var.rds_backup_retention
  deletion_protection    = var.rds_deletion_protection
  tags                   = local.common_tags
  
  depends_on = [module.secrets]
}

module "ecr" {
  source = "./modules/ecr"
  
  name_prefix = local.name_prefix
  repositories = [
    "${var.project_name}-web",
    "${var.project_name}-biometric"
  ]
  tags = local.common_tags
}

module "ecs" {
  source = "./modules/ecs"
  
  name_prefix           = local.name_prefix
  vpc_id                = module.vpc.vpc_id
  private_subnet_ids    = module.vpc.private_subnet_ids
  ecs_security_group_id = module.vpc.ecs_sg_id
  alb_security_group_id = module.alb.alb_sg_id
  ecr_repository_web    = module.ecr.repository_web_url
  ecr_repository_bio    = module.ecr.repository_biometric_url
  db_host               = module.rds.db_endpoint
  db_name               = var.db_name
  db_username           = var.db_username
  db_password           = var.db_password
  shared_secret_arn     = module.secrets.shared_secret_arn
  encryption_key_arn    = module.secrets.encryption_key_arn
  container_port_web    = 80
  container_port_bio    = 5000
  task_cpu_web          = var.task_cpu_web
  task_memory_web       = var.task_memory_web
  task_cpu_bio          = var.task_cpu_bio
  task_memory_bio       = var.task_memory_bio
  desired_count_web     = var.desired_count_web
  desired_count_bio     = var.desired_count_bio
  tags                  = local.common_tags
  
  depends_on = [module.rds, module.secrets, module.ecr]
}

module "alb" {
  source = "./modules/alb"
  
  name_prefix         = local.name_prefix
  vpc_id              = module.vpc.vpc_id
  public_subnet_ids   = module.vpc.public_subnet_ids
  ecs_security_group_id = module.vpc.ecs_sg_id
  target_group_web_arn  = module.ecs.target_group_web_arn
  target_group_bio_arn  = module.ecs.target_group_biometric_arn
  container_port_web    = 80
  container_port_bio    = 5000
  certificate_arn       = var.acm_certificate_arn
  domain_name           = var.domain_name
  tags                  = local.common_tags
  
  depends_on = [module.ecs]
}

module "cloudwatch" {
  source = "./modules/cloudwatch"
  
  name_prefix           = local.name_prefix
  ecs_cluster_name      = module.ecs.cluster_name
  ecs_service_web_name  = module.ecs.service_web_name
  ecs_service_bio_name  = module.ecs.service_biometric_name
  alb_arn               = module.alb.alb_arn
  rds_instance_id       = module.rds.db_instance_id
  log_group_web         = module.ecs.log_group_web
  log_group_bio         = module.ecs.log_group_biometric
  sns_topic_arn         = var.sns_topic_arn
  tags                  = local.common_tags
}
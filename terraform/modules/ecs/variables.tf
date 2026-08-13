variable "name_prefix" {
  type        = string
  description = "Name prefix for resources"
}

variable "vpc_id" {
  type        = string
  description = "VPC ID"
}

variable "private_subnet_ids" {
  type        = list(string)
  description = "Private subnet IDs"
}

variable "ecs_security_group_id" {
  type        = string
  description = "ECS security group ID"
}

variable "alb_security_group_id" {
  type        = string
  description = "ALB security group ID"
}

variable "ecr_repository_web" {
  type        = string
  description = "ECR repository URL for web service"
}

variable "ecr_repository_bio" {
  type        = string
  description = "ECR repository URL for biometric service"
}

variable "db_host" {
  type        = string
  description = "Database host"
}

variable "db_name" {
  type        = string
  description = "Database name"
}

variable "db_username" {
  type        = string
  description = "Database username"
}

variable "db_password" {
  type        = string
  description = "Database password"
  sensitive   = true
}

variable "shared_secret_arn" {
  type        = string
  description = "Shared secret ARN"
}

variable "encryption_key_arn" {
  type        = string
  description = "Encryption key ARN"
}

variable "container_port_web" {
  type        = number
  description = "Container port for web service"
  default     = 80
}

variable "container_port_bio" {
  type        = number
  description = "Container port for biometric service"
  default     = 5000
}

variable "task_cpu_web" {
  type        = number
  description = "CPU units for web task"
  default     = 1024
}

variable "task_memory_web" {
  type        = number
  description = "Memory for web task in MB"
  default     = 1024
}

variable "task_cpu_bio" {
  type        = number
  description = "CPU units for biometric task"
  default     = 2048
}

variable "task_memory_bio" {
  type        = number
  description = "Memory for biometric task in MB"
  default     = 2048
}

variable "desired_count_web" {
  type        = number
  description = "Desired count for web service"
  default     = 2
}

variable "desired_count_bio" {
  type        = number
  description = "Desired count for biometric service"
  default     = 2
}

variable "image_tag" {
  type        = string
  description = "Docker image tag"
  default     = "latest"
}

variable "tags" {
  type        = map(string)
  description = "Tags to apply to resources"
  default     = {}
}
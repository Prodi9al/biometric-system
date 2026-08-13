# RDS Instance
resource "aws_db_instance" "main" {
  identifier              = "${var.name_prefix}-db"
  engine                  = var.engine
  engine_version          = var.engine_version
  instance_class          = var.instance_class
  allocated_storage       = var.allocated_storage
  max_allocated_storage   = var.max_allocated_storage
  storage_type            = "gp3"
  storage_encrypted       = true
  
  db_name  = var.db_name
  username = var.username
  password = var.password
  
  vpc_security_group_ids = var.security_group_ids
  db_subnet_group_name   = aws_db_subnet_group.main.name
  
  backup_retention_period = var.backup_retention_period
  backup_window          = "03:00-04:00"
  maintenance_window     = "sun:04:00-sun:05:00"
  
  deletion_protection         = var.deletion_protection
  skip_final_snapshot         = false
  final_snapshot_identifier   = "${var.name_prefix}-db-final-snapshot-${formatdate("YYYY-MM-DD-hhmm", timestamp())}"
  
  performance_insights_enabled = true
  monitoring_interval         = 60
  monitoring_role_arn        = aws_iam_role.enhanced_monitoring.arn
  
  enabled_cloudwatch_logs_exports = ["error", "general", "slowquery", "audit"]
  
  parameter_group_name = aws_db_parameter_group.main.name
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-db" })
}

# DB Subnet Group
resource "aws_db_subnet_group" "main" {
  name       = "${var.name_prefix}-db-subnet-group"
  subnet_ids = var.database_subnet_ids
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-db-subnet-group" })
}

# Parameter Group
resource "aws_db_parameter_group" "main" {
  family = "mysql8.0"
  name   = "${var.name_prefix}-db-params"
  
  parameter {
    name  = "slow_query_log"
    value = "1"
  }
  
  parameter {
    name  = "long_query_time"
    value = "2"
  }
  
  parameter {
    name  = "log_queries_not_using_indexes"
    value = "1"
  }
  
  parameter {
    name  = "max_connections"
    value = "200"
  }
  
  parameter {
    name  = "innodb_buffer_pool_size"
    value = "{DBInstanceClassMemory*3/4}"
  }
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-db-params" })
}

# Enhanced Monitoring Role
resource "aws_iam_role" "enhanced_monitoring" {
  name = "${var.name_prefix}-rds-monitoring-role"
  
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action = "sts:AssumeRole"
      Effect = "Allow"
      Principal = {
        Service = "monitoring.rds.amazonaws.com"
      }
    }]
  })
}

resource "aws_iam_role_policy_attachment" "enhanced_monitoring" {
  role       = aws_iam_role.enhanced_monitoring.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonRDSEnhancedMonitoringRole"
}

# Outputs
output "db_endpoint" {
  value = aws_db_instance.main.endpoint
}

output "db_port" {
  value = aws_db_instance.main.port
}

output "db_instance_id" {
  value = aws_db_instance.main.id
}

output "db_arn" {
  value = aws_db_instance.main.arn
}
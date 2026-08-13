variable "name_prefix" {
  type        = string
  description = "Name prefix for resources"
}

variable "vpc_id" {
  type        = string
  description = "VPC ID"
}

variable "database_subnet_ids" {
  type        = list(string)
  description = "Database subnet IDs"
}

variable "security_group_ids" {
  type        = list(string)
  description = "Security group IDs"
}

variable "engine" {
  type        = string
  description = "Database engine"
  default     = "mysql"
}

variable "engine_version" {
  type        = string
  description = "Database engine version"
  default     = "8.0"
}

variable "instance_class" {
  type        = string
  description = "RDS instance class"
}

variable "allocated_storage" {
  type        = number
  description = "Initial allocated storage in GB"
}

variable "max_allocated_storage" {
  type        = number
  description = "Maximum allocated storage in GB"
}

variable "db_name" {
  type        = string
  description = "Database name"
}

variable "username" {
  type        = string
  description = "Database master username"
}

variable "password" {
  type        = string
  description = "Database master password"
  sensitive   = true
}

variable "backup_retention_period" {
  type        = number
  description = "Backup retention period in days"
  default     = 7
}

variable "deletion_protection" {
  type        = bool
  description = "Enable deletion protection"
  default     = true
}

variable "tags" {
  type        = map(string)
  description = "Tags to apply to resources"
  default     = {}
}
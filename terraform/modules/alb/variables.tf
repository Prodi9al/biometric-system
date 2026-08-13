variable "name_prefix" {
  type        = string
  description = "Name prefix for resources"
}

variable "vpc_id" {
  type        = string
  description = "VPC ID"
}

variable "public_subnet_ids" {
  type        = list(string)
  description = "Public subnet IDs"
}

variable "ecs_security_group_id" {
  type        = string
  description = "ECS security group ID"
}

variable "target_group_web_arn" {
  type        = string
  description = "Target group ARN for web service"
}

variable "target_group_bio_arn" {
  type        = string
  description = "Target group ARN for biometric service"
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

variable "certificate_arn" {
  type        = string
  description = "ACM certificate ARN"
}

variable "domain_name" {
  type        = string
  description = "Custom domain name"
  default     = ""
}

variable "enable_deletion_protection" {
  type        = bool
  description = "Enable ALB deletion protection"
  default     = true
}

variable "enable_access_logs" {
  type        = bool
  description = "Enable ALB access logs"
  default     = true
}

variable "access_logs_bucket" {
  type        = string
  description = "S3 bucket for ALB access logs"
  default     = ""
}

variable "enable_waf" {
  type        = bool
  description = "Enable WAF"
  default     = false
}

variable "waf_web_acl_arn" {
  type        = string
  description = "WAF Web ACL ARN"
  default     = ""
}

variable "tags" {
  type        = map(string)
  description = "Tags to apply to resources"
  default     = {}
}
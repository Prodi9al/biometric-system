variable "name_prefix" {
  type        = string
  description = "Name prefix for resources"
}

variable "repositories" {
  type        = list(string)
  description = "List of repository names"
  default     = []
}

variable "tags" {
  type        = map(string)
  description = "Tags to apply to resources"
  default     = {}
}
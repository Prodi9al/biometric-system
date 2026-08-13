# ECR Repository for Web Service
resource "aws_ecr_repository" "web" {
  name                 = "${var.name_prefix}-web"
  image_tag_mutability = "MUTABLE"
  image_scanning_configuration {
    scan_on_push = true
  }
  
  encryption_configuration {
    encryption_type = "AES256"
  }
  
  lifecycle_policy {
    policy = jsonencode({
      rules = [{
        rulePriority = 1
        description  = "Keep last 10 images"
        selection = {
          tagStatus     = "any"
          countType     = "imageCountMoreThan"
          countNumber   = 10
        }
        action = {
          type = "expire"
        }
      }]
    })
  }
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-web" })
}

# ECR Repository for Biometric Service
resource "aws_ecr_repository" "biometric" {
  name                 = "${var.name_prefix}-biometric"
  image_tag_mutability = "MUTABLE"
  image_scanning_configuration {
    scan_on_push = true
  }
  
  encryption_configuration {
    encryption_type = "AES256"
  }
  
  lifecycle_policy {
    policy = jsonencode({
      rules = [{
        rulePriority = 1
        description  = "Keep last 10 images"
        selection = {
          tagStatus     = "any"
          countType     = "imageCountMoreThan"
          countNumber   = 10
        }
        action = {
          type = "expire"
        }
      }]
    })
  }
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-biometric" })
}

# ECR Repository Policy (allow ECS to pull)
resource "aws_ecr_repository_policy" "web" {
  repository = aws_ecr_repository.web.name
  
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Sid    = "AllowECSPull"
      Effect = "Allow"
      Principal = {
        Service = "ecs-tasks.amazonaws.com"
      }
      Action = [
        "ecr:BatchGetImage",
        "ecr:GetDownloadUrlForLayer",
        "ecr:BatchCheckLayerAvailability"
      ]
    }]
  })
}

resource "aws_ecr_repository_policy" "biometric" {
  repository = aws_ecr_repository.biometric.name
  
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Sid    = "AllowECSPull"
      Effect = "Allow"
      Principal = {
        Service = "ecs-tasks.amazonaws.com"
      }
      Action = [
        "ecr:BatchGetImage",
        "ecr:GetDownloadUrlForLayer",
        "ecr:BatchCheckLayerAvailability"
      ]
    }]
  })
}

# Outputs
output "repository_web_url" {
  value = aws_ecr_repository.web.repository_url
}

output "repository_biometric_url" {
  value = aws_ecr_repository.biometric.repository_url
}

output "repository_web_name" {
  value = aws_ecr_repository.web.name
}

output "repository_biometric_name" {
  value = aws_ecr_repository.biometric.name
}
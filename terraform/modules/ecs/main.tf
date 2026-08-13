# ECS Cluster
resource "aws_ecs_cluster" "main" {
  name = "${var.name_prefix}-cluster"
  
  setting {
    name  = "containerInsights"
    value = "enabled"
  }
  
  configuration {
    execute_command_configuration {
      logging = "OVERRIDE"
      
      log_configuration {
        cloud_watch_log_group_name = aws_cloudwatch_log_group.ecs_exec.name
      }
    }
  }
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-cluster" })
}

# CloudWatch Log Groups
resource "aws_cloudwatch_log_group" "web" {
  name              = "/ecs/${var.name_prefix}-web"
  retention_in_days = 30
  
  tags = var.tags
}

resource "aws_cloudwatch_log_group" "biometric" {
  name              = "/ecs/${var.name_prefix}-biometric"
  retention_in_days = 30
  
  tags = var.tags
}

resource "aws_cloudwatch_log_group" "ecs_exec" {
  name              = "/ecs/${var.name_prefix}-exec"
  retention_in_days = 7
  
  tags = var.tags
}

# Task Execution Role
resource "aws_iam_role" "task_execution" {
  name = "${var.name_prefix}-task-execution-role"
  
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action = "sts:AssumeRole"
      Effect = "Allow"
      Principal = {
        Service = "ecs-tasks.amazonaws.com"
      }
    }]
  })
}

resource "aws_iam_role_policy" "task_execution" {
  name = "${var.name_prefix}-task-execution-policy"
  role = aws_iam_role.task_execution.id
  
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Action = [
          "ecr:GetAuthorizationToken",
          "ecr:BatchCheckLayerAvailability",
          "ecr:GetDownloadUrlForLayer",
          "ecr:BatchGetImage",
          "logs:CreateLogStream",
          "logs:PutLogEvents",
          "secretsmanager:GetSecretValue",
          "kms:Decrypt"
        ]
        Resource = "*"
      }
    ]
  })
}

# Task Role (for application permissions)
resource "aws_iam_role" "task" {
  name = "${var.name_prefix}-task-role"
  
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action = "sts:AssumeRole"
      Effect = "Allow"
      Principal = {
        Service = "ecs-tasks.amazonaws.com"
      }
    }]
  })
}

resource "aws_iam_role_policy" "task" {
  name = "${var.name_prefix}-task-policy"
  role = aws_iam_role.task.id
  
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Action = [
          "secretsmanager:GetSecretValue"
        ]
        Resource = [
          var.shared_secret_arn,
          var.encryption_key_arn
        ]
      },
      {
        Effect = "Allow"
        Action = [
          "kms:Decrypt"
        ]
        Resource = "*"
        Condition = {
          StringEquals = {
            "kms:ViaService" = "secretsmanager.${data.aws_region.current.name}.amazonaws.com"
          }
        }
      }
    ]
  })
}

data "aws_region" "current" {}

# ============================================================================
# WEB SERVICE TASK DEFINITION
# ============================================================================

resource "aws_ecs_task_definition" "web" {
  family                   = "${var.name_prefix}-web-task"
  network_mode             = "awsvpc"
  requires_compatibilities = ["FARGATE"]
  cpu                      = var.task_cpu_web
  memory                   = var.task_memory_web
  execution_role_arn       = aws_iam_role.task_execution.arn
  task_role_arn            = aws_iam_role.task.arn
  
  container_definitions = jsonencode([
    {
      name      = "web"
      image     = "${var.ecr_repository_web}:${var.image_tag}"
      cpu       = var.task_cpu_web
      memory    = var.task_memory_web
      essential = true
      portMappings = [{
        containerPort = var.container_port_web
        protocol      = "tcp"
      }]
      environment = [
        { name = "DB_HOST", value = var.db_host },
        { name = "DB_USER", value = var.db_username },
        { name = "DB_NAME", value = var.db_name },
        { name = "APP_ENV", value = "production" },
        { name = "DEBUG", value = "false" }
      ]
      secrets = [
        { name = "DB_PASS", valueFrom = var.db_password_arn },
        { name = "SHARED_SECRET", valueFrom = var.shared_secret_arn },
        { name = "ENCRYPTION_KEY", valueFrom = var.encryption_key_arn }
      ]
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.web.name
          "awslogs-region"        = data.aws_region.current.name
          "awslogs-stream-prefix" = "web"
        }
      }
      healthCheck = {
        command  = ["CMD-SHELL", "curl -f http://localhost/health.php || exit 1"]
        interval = 30
        timeout  = 10
        retries  = 3
        startPeriod = 60
      }
    }
  ])
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-web-task" })
}

# ============================================================================
# BIOMETRIC SERVICE TASK DEFINITION
# ============================================================================

resource "aws_ecs_task_definition" "biometric" {
  family                   = "${var.name_prefix}-biometric-task"
  network_mode             = "awsvpc"
  requires_compatibilities = ["FARGATE"]
  cpu                      = var.task_cpu_bio
  memory                   = var.task_memory_bio
  execution_role_arn       = aws_iam_role.task_execution.arn
  task_role_arn            = aws_iam_role.task.arn
  
  container_definitions = jsonencode([
    {
      name      = "biometric"
      image     = "${var.ecr_repository_bio}:${var.image_tag}"
      cpu       = var.task_cpu_bio
      memory    = var.task_memory_bio
      essential = true
      portMappings = [{
        containerPort = var.container_port_bio
        protocol      = "tcp"
      }]
      environment = [
        { name = "DB_HOST", value = var.db_host },
        { name = "DB_USER", value = var.db_username },
        { name = "DB_NAME", value = var.db_name },
        { name = "APP_ENV", value = "production" }
      ]
      secrets = [
        { name = "DB_PASS", valueFrom = var.db_password_arn },
        { name = "SHARED_SECRET", valueFrom = var.shared_secret_arn },
        { name = "ENCRYPTION_KEY", valueFrom = var.encryption_key_arn }
      ]
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.biometric.name
          "awslogs-region"        = data.aws_region.current.name
          "awslogs-stream-prefix" = "biometric"
        }
      }
      healthCheck = {
        command  = ["CMD-SHELL", "curl -f http://localhost:5000/health || exit 1"]
        interval = 30
        timeout  = 10
        retries  = 3
        startPeriod = 60
      }
    }
  ])
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-biometric-task" })
}

# ============================================================================
# TARGET GROUPS
# ============================================================================

resource "aws_lb_target_group" "web" {
  name        = "${var.name_prefix}-web-tg"
  port        = var.container_port_web
  protocol    = "HTTP"
  vpc_id      = var.vpc_id
  target_type = "ip"
  
  health_check {
    path                = "/health.php"
    healthy_threshold   = 2
    unhealthy_threshold = 3
    timeout             = 10
    interval            = 30
    matcher             = "200"
  }
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-web-tg" })
}

resource "aws_lb_target_group" "biometric" {
  name        = "${var.name_prefix}-biometric-tg"
  port        = var.container_port_bio
  protocol    = "HTTP"
  vpc_id      = var.vpc_id
  target_type = "ip"
  
  health_check {
    path                = "/health"
    healthy_threshold   = 2
    unhealthy_threshold = 3
    timeout             = 10
    interval            = 30
    matcher             = "200"
  }
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-biometric-tg" })
}

# ============================================================================
# ECS SERVICES
# ============================================================================

resource "aws_ecs_service" "web" {
  name            = "${var.name_prefix}-web-service"
  cluster         = aws_ecs_cluster.main.id
  task_definition = aws_ecs_task_definition.web.arn
  desired_count   = var.desired_count_web
  launch_type     = "FARGATE"
  
  network_configuration {
    subnets         = var.private_subnet_ids
    security_groups = [var.ecs_security_group_id]
    assign_public_ip = false
  }
  
  load_balancer {
    target_group_arn = aws_lb_target_group.web.arn
    container_name   = "web"
    container_port   = var.container_port_web
  }
  
  deployment_controller {
    type = "ECS"
  }
  
  deployment_circuit_breaker {
    enable   = true
    rollback = true
  }
  
  deployment_maximum_percent         = 200
  deployment_minimum_healthy_percent = 50
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-web-service" })
  
  depends_on = [aws_lb_target_group.web]
}

resource "aws_ecs_service" "biometric" {
  name            = "${var.name_prefix}-biometric-service"
  cluster         = aws_ecs_cluster.main.id
  task_definition = aws_ecs_task_definition.biometric.arn
  desired_count   = var.desired_count_bio
  launch_type     = "FARGATE"
  
  network_configuration {
    subnets         = var.private_subnet_ids
    security_groups = [var.ecs_security_group_id]
    assign_public_ip = false
  }
  
  load_balancer {
    target_group_arn = aws_lb_target_group.biometric.arn
    container_name   = "biometric"
    container_port   = var.container_port_bio
  }
  
  deployment_controller {
    type = "ECS"
  }
  
  deployment_circuit_breaker {
    enable   = true
    rollback = true
  }
  
  deployment_maximum_percent         = 200
  deployment_minimum_healthy_percent = 50
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-biometric-service" })
  
  depends_on = [aws_lb_target_group.biometric]
}

# ============================================================================
# SERVICE AUTO SCALING
# ============================================================================

resource "aws_appautoscaling_target" "web" {
  max_capacity       = 10
  min_capacity       = var.desired_count_web
  resource_id        = "service/${aws_ecs_cluster.main.name}/${aws_ecs_service.web.name}"
  scalable_dimension = "ecs:service:DesiredCount"
  service_namespace  = "ecs"
}

resource "aws_appautoscaling_policy" "web_cpu" {
  name               = "${var.name_prefix}-web-cpu-scaling"
  policy_type        = "TargetTrackingScaling"
  resource_id        = aws_appautoscaling_target.web.resource_id
  scalable_dimension = aws_appautoscaling_target.web.scalable_dimension
  service_namespace  = aws_appautoscaling_target.web.service_namespace
  
  target_tracking_scaling_policy_configuration {
    predefined_metric_specification {
      predefined_metric_type = "ECSServiceAverageCPUUtilization"
    }
    target_value       = 70.0
    scale_in_cooldown  = 300
    scale_out_cooldown = 300
  }
}

resource "aws_appautoscaling_policy" "web_memory" {
  name               = "${var.name_prefix}-web-memory-scaling"
  policy_type        = "TargetTrackingScaling"
  resource_id        = aws_appautoscaling_target.web.resource_id
  scalable_dimension = aws_appautoscaling_target.web.scalable_dimension
  service_namespace  = aws_appautoscaling_target.web.service_namespace
  
  target_tracking_scaling_policy_configuration {
    predefined_metric_specification {
      predefined_metric_type = "ECSServiceAverageMemoryUtilization"
    }
    target_value       = 80.0
    scale_in_cooldown  = 300
    scale_out_cooldown = 300
  }
}

resource "aws_appautoscaling_target" "biometric" {
  max_capacity       = 5
  min_capacity       = var.desired_count_bio
  resource_id        = "service/${aws_ecs_cluster.main.name}/${aws_ecs_service.biometric.name}"
  scalable_dimension = "ecs:service:DesiredCount"
  service_namespace  = "ecs"
}

resource "aws_appautoscaling_policy" "biometric_cpu" {
  name               = "${var.name_prefix}-biometric-cpu-scaling"
  policy_type        = "TargetTrackingScaling"
  resource_id        = aws_appautoscaling_target.biometric.resource_id
  scalable_dimension = aws_appautoscaling_target.biometric.scalable_dimension
  service_namespace  = aws_appautoscaling_target.biometric.service_namespace
  
  target_tracking_scaling_policy_configuration {
    predefined_metric_specification {
      predefined_metric_type = "ECSServiceAverageCPUUtilization"
    }
    target_value       = 70.0
    scale_in_cooldown  = 300
    scale_out_cooldown = 300
  }
}

# Outputs
output "cluster_name" {
  value = aws_ecs_cluster.main.name
}

output "cluster_arn" {
  value = aws_ecs_cluster.main.arn
}

output "service_web_name" {
  value = aws_ecs_service.web.name
}

output "service_biometric_name" {
  value = aws_ecs_service.biometric.name
}

output "task_definition_web_arn" {
  value = aws_ecs_task_definition.web.arn
}

output "task_definition_biometric_arn" {
  value = aws_ecs_task_definition.biometric.arn
}

output "target_group_web_arn" {
  value = aws_lb_target_group.web.arn
}

output "target_group_biometric_arn" {
  value = aws_lb_target_group.biometric.arn
}

output "log_group_web" {
  value = aws_cloudwatch_log_group.web.name
}

output "log_group_biometric" {
  value = aws_cloudwatch_log_group.biometric.name
}
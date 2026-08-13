# Application Load Balancer
resource "aws_lb" "main" {
  name               = "${var.name_prefix}-alb"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [var.alb_security_group_id]
  subnets            = var.public_subnet_ids
  enable_deletion_protection = var.enable_deletion_protection
  
  access_logs {
    bucket  = var.access_logs_bucket
    prefix  = "${var.name_prefix}/alb"
    enabled = var.enable_access_logs
  }
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-alb" })
}

# ALB Security Group
resource "aws_security_group" "alb" {
  name        = "${var.name_prefix}-alb-sg"
  description = "Security group for ALB"
  vpc_id      = var.vpc_id
  
  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }
  
  ingress {
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }
  
  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-alb-sg" })
}

# HTTP Listener (redirect to HTTPS)
resource "aws_lb_listener" "http" {
  load_balancer_arn = aws_lb.main.arn
  port              = 80
  protocol          = "HTTP"
  
  default_action {
    type = "redirect"
    redirect {
      port        = "443"
      protocol    = "HTTPS"
      status_code = "HTTP_301"
    }
  }
}

# HTTPS Listener
resource "aws_lb_listener" "https" {
  load_balancer_arn = aws_lb.main.arn
  port              = 443
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS13-1-2-2021-06"
  certificate_arn   = var.certificate_arn
  
  default_action {
    type             = "forward"
    target_group_arn = var.target_group_web_arn
  }
}

# Listener Rule for Biometric Service (path-based routing)
resource "aws_lb_listener_rule" "biometric" {
  listener_arn = aws_lb_listener.https.arn
  priority     = 100
  
  action {
    type             = "forward"
    target_group_arn = var.target_group_bio_arn
  }
  
  condition {
    path_pattern {
      values = ["/api/biometric/*", "/verify", "/health"]
    }
  }
}

# Listener Rule for Web Service (catch-all)
resource "aws_lb_listener_rule" "web" {
  listener_arn = aws_lb_listener.https.arn
  priority     = 200
  
  action {
    type             = "forward"
    target_group_arn = var.target_group_web_arn
  }
  
  condition {
    path_pattern {
      values = ["/*"]
    }
  }
}

# WAF Association (if WAF is enabled)
resource "aws_wafv2_web_acl_association" "main" {
  count      = var.enable_waf ? 1 : 0
  resource_arn = aws_lb.main.arn
  web_acl_arn  = var.waf_web_acl_arn
}

# Outputs
output "alb_arn" {
  value = aws_lb.main.arn
}

output "alb_dns_name" {
  value = aws_lb.main.dns_name
}

output "alb_zone_id" {
  value = aws_lb.main.zone_id
}

output "alb_sg_id" {
  value = aws_security_group.alb.id
}

output "https_listener_arn" {
  value = aws_lb_listener.https.arn
}
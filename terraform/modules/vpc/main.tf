# VPC
resource "aws_vpc" "main" {
  cidr_block           = var.vpc_cidr
  enable_dns_hostnames = true
  enable_dns_support   = true
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-vpc" })
}

# Internet Gateway
resource "aws_internet_gateway" "main" {
  vpc_id = aws_vpc.main.id
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-igw" })
}

# Public Subnets
resource "aws_subnet" "public" {
  count                   = length(var.public_subnet_cidrs)
  vpc_id                  = aws_vpc.main.id
  cidr_block              = var.public_subnet_cidrs[count.index]
  availability_zone       = var.availability_zones[count.index]
  map_public_ip_on_launch = true
  
  tags = merge(var.tags, { 
    Name = "${var.name_prefix}-public-${var.availability_zones[count.index]}"
    Type = "public"
  })
}

# Private Subnets (for ECS tasks)
resource "aws_subnet" "private" {
  count             = length(var.private_subnet_cidrs)
  vpc_id            = aws_vpc.main.id
  cidr_block        = var.private_subnet_cidrs[count.index]
  availability_zone = var.availability_zones[count.index]
  
  tags = merge(var.tags, { 
    Name = "${var.name_prefix}-private-${var.availability_zones[count.index]}"
    Type = "private"
  })
}

# Database Subnets (isolated)
resource "aws_subnet" "database" {
  count             = length(var.database_subnet_cidrs)
  vpc_id            = aws_vpc.main.id
  cidr_block        = var.database_subnet_cidrs[count.index]
  availability_zone = var.availability_zones[count.index]
  
  tags = merge(var.tags, { 
    Name = "${var.name_prefix}-database-${var.availability_zones[count.index]}"
    Type = "database"
  })
}

# NAT Gateways (one per AZ for HA)
resource "aws_eip" "nat" {
  count  = length(var.availability_zones)
  domain = "vpc"
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-nat-eip-${var.availability_zones[count.index]}" })
}

resource "aws_nat_gateway" "main" {
  count         = length(var.availability_zones)
  allocation_id = aws_eip.nat[count.index].id
  subnet_id     = aws_subnet.public[count.index].id
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-nat-${var.availability_zones[count.index]}" })
  
  depends_on = [aws_internet_gateway.main]
}

# Route Tables
resource "aws_route_table" "public" {
  vpc_id = aws_vpc.main.id
  
  route {
    cidr_block = "0.0.0.0/0"
    gateway_id = aws_internet_gateway.main.id
  }
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-public-rt" })
}

resource "aws_route_table_association" "public" {
  count          = length(var.public_subnet_cidrs)
  subnet_id      = aws_subnet.public[count.index].id
  route_table_id = aws_route_table.public.id
}

resource "aws_route_table" "private" {
  count  = length(var.availability_zones)
  vpc_id = aws_vpc.main.id
  
  route {
    cidr_block     = "0.0.0.0/0"
    nat_gateway_id = aws_nat_gateway.main[count.index].id
  }
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-private-rt-${var.availability_zones[count.index]}" })
}

resource "aws_route_table_association" "private" {
  count          = length(var.availability_zones)
  subnet_id      = aws_subnet.private[count.index].id
  route_table_id = aws_route_table.private[count.index].id
}

resource "aws_route_table" "database" {
  vpc_id = aws_vpc.main.id
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-database-rt" })
}

resource "aws_route_table_association" "database" {
  count          = length(var.database_subnet_cidrs)
  subnet_id      = aws_subnet.database[count.index].id
  route_table_id = aws_route_table.database.id
}

# Security Groups
resource "aws_security_group" "alb" {
  name        = "${var.name_prefix}-alb-sg"
  description = "Security group for ALB"
  vpc_id      = aws_vpc.main.id
  
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

resource "aws_security_group" "ecs" {
  name        = "${var.name_prefix}-ecs-sg"
  description = "Security group for ECS tasks"
  vpc_id      = aws_vpc.main.id
  
  ingress {
    from_port       = 80
    to_port         = 80
    protocol        = "tcp"
    security_groups = [aws_security_group.alb.id]
  }
  
  ingress {
    from_port       = 5000
    to_port         = 5000
    protocol        = "tcp"
    security_groups = [aws_security_group.alb.id]
  }
  
  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-ecs-sg" })
}

resource "aws_security_group" "database" {
  name        = "${var.name_prefix}-database-sg"
  description = "Security group for RDS"
  vpc_id      = aws_vpc.main.id
  
  ingress {
    from_port       = 3306
    to_port         = 3306
    protocol        = "tcp"
    security_groups = [aws_security_group.ecs.id]
  }
  
  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-database-sg" })
}

# DB Subnet Group
resource "aws_db_subnet_group" "main" {
  name       = "${var.name_prefix}-db-subnet-group"
  subnet_ids = aws_subnet.database[*].id
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-db-subnet-group" })
}

# VPC Flow Logs
resource "aws_flow_log" "main" {
  iam_role_arn    = aws_iam_role.flow_logs.arn
  log_destination = aws_cloudwatch_log_group.flow_logs.arn
  traffic_type    = "ALL"
  vpc_id          = aws_vpc.main.id
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-flow-logs" })
}

resource "aws_iam_role" "flow_logs" {
  name = "${var.name_prefix}-flow-logs-role"
  
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action = "sts:AssumeRole"
      Effect = "Allow"
      Principal = {
        Service = "vpc-flow-logs.amazonaws.com"
      }
    }]
  })
}

resource "aws_iam_role_policy" "flow_logs" {
  role = aws_iam_role.flow_logs.name
  
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action = [
        "logs:CreateLogGroup",
        "logs:CreateLogStream",
        "logs:PutLogEvents",
        "logs:DescribeLogGroups",
        "logs:DescribeLogStreams"
      ]
      Effect   = "Allow"
      Resource = "*"
    }]
  })
}

resource "aws_cloudwatch_log_group" "flow_logs" {
  name              = "/aws/vpc/flowlogs/${var.name_prefix}"
  retention_in_days = 30
  
  tags = var.tags
}

# Outputs
output "vpc_id" {
  value = aws_vpc.main.id
}

output "public_subnet_ids" {
  value = aws_subnet.public[*].id
}

output "private_subnet_ids" {
  value = aws_subnet.private[*].id
}

output "database_subnet_ids" {
  value = aws_subnet.database[*].id
}

output "alb_sg_id" {
  value = aws_security_group.alb.id
}

output "ecs_sg_id" {
  value = aws_security_group.ecs.id
}

output "database_sg_id" {
  value = aws_security_group.database.id
}
# CloudFleet AWS

CloudFleet is a PHP fleet-operations application designed to be used as an AWS learning project.

## Included application modules

- Dashboard
- Vehicles
- Drivers and licence expiry monitoring
- Tours
- Tour lifecycle: Scheduled → In Progress → Completed / Cancelled
- Intelligent assignment validation
- Vehicle capacity checks
- Driver licence checks
- Vehicle/driver schedule conflict detection
- Operations Schedule
- Assignment/resource utilization dashboard
- Maintenance records
- Amazon S3-ready Documents module
- GitHub Actions → AWS OIDC → Elastic Beanstalk CI/CD

## AWS architecture

Browser → Elastic Beanstalk → EC2/PHP → Amazon RDS MySQL

GitHub push → GitHub Actions → OIDC → IAM Role → S3 deployment bundle → Elastic Beanstalk

Optional Documents:
EC2/PHP → IAM Instance Profile → Amazon S3

## Elastic Beanstalk environment properties

Required now:

- `RDS_HOSTNAME`
- `RDS_PORT`
- `RDS_DB_NAME`
- `RDS_USERNAME`
- `RDS_PASSWORD`

For the S3 lesson later:

- `AWS_REGION=ap-south-1`
- `S3_BUCKET=<your-private-documents-bucket>`

For the one-time schema setup only:

- `ALLOW_MIGRATIONS=1`

Visit `/database/migrate.php` once, verify success, then immediately remove or set `ALLOW_MIGRATIONS=0`.

## Important

Do not commit real passwords, AWS access keys, or secret keys.
Use the Elastic Beanstalk environment properties and AWS IAM roles.

## CI/CD

This package already contains `.github/workflows/deploy.yml` matching:

- AWS account: `928003954894`
- Region: `ap-south-1`
- Application: `CloudFleet`
- Environment: `CloudFleet-dev`
- GitHub OIDC role: `CloudFleetGitHubDeployRole`
- Deployment bucket: `cloudfleet-deployments-928003954894`

If any of these names change, update the workflow.

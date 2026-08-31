# CloudFleet

CloudFleet is a small fleet and tour management system that I built while learning AWS and preparing for my AWS certification exam.

I did not build this as a production company system. My main goal was to take the AWS services I was learning and connect them to one real PHP project so I could understand how they actually work together.

I started with a normal PHP + MySQL application and then slowly moved different parts to AWS. During the project I had to fix deployment errors, IAM permission issues, database migration problems, S3 access problems and Lambda trigger issues. That troubleshooting was probably the most useful part of the project for me.

## What the system does

CloudFleet can be used to manage basic fleet operations such as:

- Vehicles
- Drivers
- Tours
- Vehicle and driver assignments
- Tour schedules
- Maintenance records
- Driver licence details
- Tour status updates
- Documents related to vehicles, drivers and tours

The document section is the main AWS-focused part of the project.

## AWS services I used

### Elastic Beanstalk

I deployed the PHP application using AWS Elastic Beanstalk instead of manually managing the server.

### Amazon RDS

The application uses an Amazon RDS MySQL database for storing vehicles, drivers, tours, assignments, maintenance records and document information.

### Amazon S3

Documents are stored privately in Amazon S3 instead of storing uploaded files directly inside the web server.

The S3 document section supports:

- Uploading files
- Private storage
- Secure downloads using presigned URLs
- S3 versioning
- Delete markers
- Recycle bin
- Restoring deleted files

### AWS IAM

I used IAM roles instead of putting AWS access keys directly inside the PHP code.

The Elastic Beanstalk EC2 instance role is allowed to access only the S3 actions the application needs.

### AWS Lambda

I created a Lambda function called `CloudFleetDocumentProcessor`.

When CloudFleet uploads a new file to S3, the S3 `ObjectCreated` event automatically triggers the Lambda function.

The Lambda function adds these tags to the uploaded object:

```text
cloudfleet-processed = true
processed-by         = lambda
file-type            = jpg / jpeg / png / pdf
```

This helped me understand event-driven architecture in a practical way instead of only reading about Lambda.

### CloudWatch Logs

The Lambda function writes its execution logs to Amazon CloudWatch Logs. I used this while debugging the Lambda permissions and S3 trigger.

### GitHub Actions + AWS OIDC

The project is connected to GitHub Actions for deployment.

When I push changes to the main branch, GitHub Actions deploys the new version to Elastic Beanstalk.

I used AWS OIDC for GitHub authentication, so I did not need to store long-term AWS access keys inside GitHub secrets.

## Simple architecture

```text
My PC
  |
  | git push
  v
GitHub
  |
  v
GitHub Actions
  |
  | AWS OIDC
  v
AWS IAM
  |
  v
Elastic Beanstalk
  |
  +-----------------------------+
  |                             |
  v                             v
Amazon RDS                   Amazon S3
MySQL                        Private files
                                |
                                | ObjectCreated event
                                v
                            AWS Lambda
                                |
                                v
                         CloudWatch Logs
```

## S3 document flow

### Upload

```text
User uploads file
      -> CloudFleet PHP
      -> Amazon S3
      -> S3 ObjectCreated event
      -> AWS Lambda
      -> Lambda adds object tags
```

### Download

```text
User clicks Download
      -> CloudFleet creates a temporary presigned URL
      -> Browser downloads the private S3 object
```

The bucket stays private.

### Delete

```text
User clicks Delete
      -> S3 creates a delete marker
      -> RDS record gets a deleted_at value
      -> Document moves to the CloudFleet Recycle Bin
```

### Restore

```text
User clicks Restore
      -> CloudFleet removes the S3 delete marker
      -> RDS record becomes active again
      -> Document appears again in CloudFleet
```

## Project folders

```text
cloudfleet/
├── .github/workflows/        GitHub Actions deployment
├── app/                      tour rules
├── assignments/              vehicle/driver assignments
├── assets/                   CSS and JavaScript
├── aws/                      Lambda code and IAM policy examples
├── config/                   database connection
├── database/                 schema and migrations
├── documents/                S3 upload/download/delete/restore
├── drivers/                  driver management
├── includes/                 shared PHP files
├── maintenance/              maintenance records
├── schedule/                 schedule view
├── tours/                    tour management
├── vehicles/                 vehicle management
├── composer.json
└── index.php
```

## Environment variables

I keep database and AWS configuration outside the source code.

The Elastic Beanstalk environment uses values such as:

```text
RDS_HOSTNAME
RDS_PORT
RDS_DB_NAME
RDS_USERNAME
RDS_PASSWORD
AWS_REGION
S3_BUCKET
```

For database migrations I temporarily used:

```text
ALLOW_MIGRATIONS=1
```

After running the migration I removed/disabled it again.

## Local setup

For local testing I used XAMPP.

Basic setup:

1. Clone the repository.
2. Run `composer install`.
3. Create a MySQL database.
4. Add the required environment/database settings.
5. Run the migration.
6. Start Apache/MySQL using XAMPP.

## Security things I tried to follow

This is still a student project, but I tried to follow the AWS security ideas I learned during the project:

- No AWS access key or secret key inside the PHP files
- IAM roles for AWS access
- S3 Block Public Access enabled
- Private S3 objects
- Presigned URLs for downloads
- Least-privilege S3 permissions
- S3 versioning for recovery
- Prepared SQL statements
- CSRF tokens for write actions
- GitHub OIDC instead of long-lived AWS deployment keys

## Problems I had to solve

A few things broke while I was building this project, and fixing them helped me understand AWS better:

- RDS connection and database configuration problems
- IAM permissions for S3 upload/download/delete
- S3 versioning and delete-marker behaviour
- HTTP 500 errors after database schema changes
- Git authentication and SSH setup on Windows
- Lambda not writing to CloudWatch because of missing permissions
- Lambda running but not tagging objects because of IAM/configuration issues
- GitHub Actions deployment to Elastic Beanstalk

I kept these parts in the project because the main purpose was hands-on learning, not just making screenshots of AWS services.

## What I learned from this project

Before this project I knew many AWS services mainly from lectures/videos and exam preparation. Building CloudFleet helped me understand how the services connect in a real application.

The biggest things I learned were:

- Why IAM roles are better than hard-coded credentials
- How an application running in Elastic Beanstalk can access S3
- How RDS and S3 have different jobs in the same application
- How presigned URLs allow access to private S3 files
- How S3 versioning and delete markers work
- How S3 events can automatically trigger Lambda
- How CloudWatch helps when something fails
- How GitHub Actions can deploy to AWS using OIDC

## Current status

The project is currently a working hands-on demo that I built while learning AWS.

I plan to keep it mainly as a learning and portfolio project rather than continuously adding features to it.

---

**Built as a student hands-on AWS project using PHP, MySQL and AWS.**

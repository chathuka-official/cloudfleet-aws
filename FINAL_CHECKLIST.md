# CloudFleet final checklist

Before sharing the project publicly:

- [ ] Elastic Beanstalk environment health is OK
- [ ] RDS is not publicly accessible unless there is a specific reason
- [ ] S3 Block Public Access is enabled
- [ ] S3 versioning is enabled
- [ ] Upload, download, delete and restore work
- [ ] A new S3 upload receives the Lambda tags
- [ ] Lambda can write to CloudWatch Logs
- [ ] `ALLOW_MIGRATIONS` is removed or set to `0`
- [ ] No `.env` file is committed
- [ ] No AWS access keys, passwords or private SSH keys are committed
- [ ] `git status` is clean before the final push

import boto3
import os
import urllib.parse

s3 = boto3.client("s3")


def lambda_handler(event, context):
    print("=== CloudFleet Document Processor Started ===")

    records = event.get("Records", [])

    if not records:
        print("No S3 event records received")
        return {
            "statusCode": 400,
            "body": "No S3 event received"
        }

    for record in records:
        bucket = record["s3"]["bucket"]["name"]
        key = urllib.parse.unquote_plus(record["s3"]["object"]["key"])

        extension = os.path.splitext(key)[1].lower()
        file_type = extension[1:] if extension else "unknown"

        print("Bucket:", bucket)
        print("Object:", key)
        print("File type:", file_type)

        s3.put_object_tagging(
            Bucket=bucket,
            Key=key,
            Tagging={
                "TagSet": [
                    {
                        "Key": "cloudfleet-processed",
                        "Value": "true"
                    },
                    {
                        "Key": "processed-by",
                        "Value": "lambda"
                    },
                    {
                        "Key": "file-type",
                        "Value": file_type
                    }
                ]
            }
        )

        print("SUCCESS: Object tagged")

    print("=== CloudFleet Document Processor Finished ===")

    return {
        "statusCode": 200,
        "body": "CloudFleet document processed"
    }

Postman collection for BillPayment API

Setup

- Create a Postman environment and set the following variables:
  - `base_url` (e.g. http://localhost:8000)
  - `LYTEPAY_API_KEY` (value for `X-API-KEY`)
  - `LYTEPAY_SECRET` (value used to compute HMAC `X-Signature`)
  - `SERVER_TOKEN` (value for `X-SERVER-TOKEN`)

Usage

- Import `BillPayment.postman_collection.json` into Postman.
- The `Vend Entertainment` request includes a pre-request script that computes `X-Timestamp` and `X-Signature` using HMAC-SHA256 over the raw request body and the `LYTEPAY_SECRET` environment variable.
- Ensure your local app is running and `base_url` points at it, then send the request.

Notes

- The collection's pre-request script uses `CryptoJS` which is available in Postman native/pre-request environment.
- If you prefer, set collection-level variables instead of environment variables.

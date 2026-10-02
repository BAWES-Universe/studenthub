# OneSignal environment configuration

Candidate push notifications use OneSignal through `common\models\MobileNotification`.

Set these variables in each runtime environment instead of committing OneSignal values to `params-local.php`:

- `ONESIGNAL_CANDIDATE_APP_ID`
- `ONESIGNAL_CANDIDATE_API_KEY`

If either variable is missing, candidate push notification sending fails closed and logs a warning. The application should still continue handling the request that attempted to enqueue the notification.

The OneSignal API request keeps TLS peer verification enabled. Do not disable `CURLOPT_SSL_VERIFYPEER` for this integration.

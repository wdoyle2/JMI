# API Export (JSON/CSV)

## Overview

The application provides two authenticated export endpoints:

- `GET /api/anemometers/export`
- `GET /api/readings/export`

Both endpoints support JSON and CSV downloads, validated filters, consistent filenames, and Laravel Sanctum authentication. If `format` is omitted, JSON is used by default.

## Implementation choices

- **Spatie Laravel Data** defines stable, typed export structures independently of the normal API resources.
- **League CSV** handles CSV quoting, delimiters, and escaping safely.
- **Spatie Query Builder** provides controlled, allow-listed filters.
- **Form Requests** validate formats, dates, and anemometer IDs before querying.
- **ExportResponseFactory** centralises JSON/CSV generation and download headers.
- **ReadingExportQuery** separates filtering from response handling, making it independently testable.
- Both routes are inside `auth:sanctum`, preventing unauthenticated exports.

Exports are not paginated: each download contains the complete filtered result set. The database
queries are consumed lazily in 500-row chunks, and both JSON and CSV are streamed directly to the
client, so server memory usage does not grow with the total export size.

## Exported data

### Anemometers

Each record contains:

```json
{
  "id": "uuid",
  "name": "North Tower",
  "longitude": "1.234567",
  "latitude": "52.123456",
  "average_daily_speed": 10.5,
  "average_weekly_speed": 15.2
}
```

- `average_daily_speed` covers the previous 24 hours.
- `average_weekly_speed` covers the previous seven days.
- Averages are `null` in JSON and empty in CSV when there are no readings in the relevant period.
- Anemometers can be filtered by a partial name match.

### Readings

Each record contains:

```json
{
  "id": "uuid",
  "speed": 12.5,
  "recorded_at": "2026-07-17T09:00:00.000000Z",
  "tags": ["gusty", "coastal"],
  "anemometer_id": "uuid",
  "anemometer_name": "North Tower"
}
```

CSV uses the same fields, with tags joined into one properly escaped CSV cell.

Reading filters support:

- One or multiple anemometers
- Inclusive start and end dates
- Any matching tag
- An exact tag-set match
- Combinations of these filters

## Authentication

Obtain a Sanctum token

All export requests require:

```bash
--header "Authorization: Bearer $TOKEN"
```

## Anemometer exports

### Export all as JSON

```bash
curl --fail --location \
  --header "Authorization: Bearer $TOKEN" \
  "$BASE_URL/api/anemometers/export?format=json" \
  --output anemometers.json
```

Because JSON is the default, this is equivalent:

```bash
curl --fail --location \
  --header "Authorization: Bearer $TOKEN" \
  "$BASE_URL/api/anemometers/export" \
  --output anemometers.json
```

### Export all as CSV

```bash
curl --fail --location \
  --header "Authorization: Bearer $TOKEN" \
  "$BASE_URL/api/anemometers/export?format=csv" \
  --output anemometers.csv
```

### Filter by partial name

```bash
curl --fail --location --get \
  --header "Authorization: Bearer $TOKEN" \
  --data-urlencode "format=json" \
  --data-urlencode "filter[name]=North" \
  "$BASE_URL/api/anemometers/export" \
  --output anemometers-north.json
```

## Reading exports

### Export all as JSON

```bash
curl --fail --location \
  --header "Authorization: Bearer $TOKEN" \
  "$BASE_URL/api/readings/export?format=json" \
  --output readings.json
```

### Export all as CSV

```bash
curl --fail --location \
  --header "Authorization: Bearer $TOKEN" \
  "$BASE_URL/api/readings/export?format=csv" \
  --output readings.csv
```

### Filter by one anemometer

```bash
ANEMOMETER_ID="11111111-1111-1111-1111-111111111111"

curl --fail --location --get \
  --header "Authorization: Bearer $TOKEN" \
  --data-urlencode "format=csv" \
  --data-urlencode "filter[anemometer]=$ANEMOMETER_ID" \
  "$BASE_URL/api/readings/export" \
  --output readings-anemometer.csv
```

### Filter by multiple anemometers

```bash
ANEMOMETER_IDS="11111111-1111-1111-1111-111111111111,22222222-2222-2222-2222-222222222222"

curl --fail --location --get \
  --header "Authorization: Bearer $TOKEN" \
  --data-urlencode "format=json" \
  --data-urlencode "filter[anemometer]=$ANEMOMETER_IDS" \
  "$BASE_URL/api/readings/export" \
  --output readings-anemometers.json
```

### Filter by date range

Both bounds are inclusive:

```bash
curl --fail --location --get \
  --header "Authorization: Bearer $TOKEN" \
  --data-urlencode "format=csv" \
  --data-urlencode "filter[recorded_at_after]=2026-07-01T00:00:00Z" \
  --data-urlencode "filter[recorded_at_before]=2026-07-17T23:59:59Z" \
  "$BASE_URL/api/readings/export" \
  --output readings-july.csv
```

### Match any requested tag

Returns readings containing `gusty` or `coastal`:

```bash
curl --fail --location --get \
  --header "Authorization: Bearer $TOKEN" \
  --data-urlencode "format=json" \
  --data-urlencode "tags_any=gusty,coastal" \
  "$BASE_URL/api/readings/export" \
  --output readings-tags-any.json
```

### Match an exact tag set

Returns readings whose complete tag set is exactly `gusty,coastal`:

```bash
curl --fail --location --get \
  --header "Authorization: Bearer $TOKEN" \
  --data-urlencode "format=json" \
  --data-urlencode "tags_exact=gusty,coastal" \
  "$BASE_URL/api/readings/export" \
  --output readings-tags-exact.json
```

### Combine filters

```bash
curl --fail --location --get \
  --header "Authorization: Bearer $TOKEN" \
  --data-urlencode "format=csv" \
  --data-urlencode "filter[anemometer]=$ANEMOMETER_IDS" \
  --data-urlencode "filter[recorded_at_after]=2026-07-01T00:00:00Z" \
  --data-urlencode "filter[recorded_at_before]=2026-07-17T23:59:59Z" \
  --data-urlencode "tags_any=gusty,coastal" \
  "$BASE_URL/api/readings/export" \
  --output filtered-readings.csv
```

## Errors

- Missing or invalid authentication returns HTTP `401`.
- Invalid formats, dates, or anemometer identifiers return HTTP `422`.

# Frontend Migration to Meilisearch

## Status: ✅ Backend Ready

The backend has been migrated to Meilisearch. All Algolia references have been removed.

## Security

The backend proxy approach is secure because:
- **Meilisearch is internal**: Not exposed to the internet
- **Temporary keys**: Keys expire after 2 minutes
- **Index restrictions**: Keys are limited to specific indexes only
- **Search-only permissions**: Keys can only perform search operations
- **Backend validation**: All requests go through authenticated backend

## What Changed

### New Endpoint

- **Key Endpoint**: `GET /v1/meilisearch/key` (was `/v1/algolia/key`)
- **Search Endpoint**: `POST /v1/meilisearch/search` (was `/v1/algolia/search`)

### Response Format

**Key Response (`GET /v1/meilisearch/key`):**
```json
{
  "host": "http://meilisearch:7700",
  "apiKey": "temporary_search_key_abc123...",
  "apiKeyValidUntil": 1731446400
}
```

**Search Response (`POST /v1/meilisearch/search`):**
```json
{
  "results": [{
    "hits": [...],
    "nbHits": 100,
    "nbPages": 5,
    "page": 0,
    "processingTimeMS": 10,
    "query": "search term"
  }]
}
```

## Required Frontend Changes

### 1. Update Service Endpoints

**File**: `src/app/providers/logged-in/algolia.service.ts` (consider renaming to `meilisearch.service.ts`)

**Update `getKey()` method:**
```typescript
async getKey(isExpired: boolean = false): Promise<any> {
  if (this.cachedKey && !isExpired && this.cachedKey.apiKeyValidUntil && 
      Date.now() / 1000 < this.cachedKey.apiKeyValidUntil) {
    return this.cachedKey;
  }

  try {
    const response = await this.http.get<any>(`${this.apiUrl}/meilisearch/key`).toPromise();
    
    const keyData = {
      host: response.host,
      apiKey: response.apiKey,
      apiKeyValidUntil: response.apiKeyValidUntil
    };
    
    this.cachedKey = keyData;
    return keyData;
  } catch (error) {
    if (error.status === 400) {
      return this.getKey(true);
    }
    throw error;
  }
}
```

### 2. Update Search Method

**Use the backend proxy endpoint** (Meilisearch is internal, so all searches go through the backend):

```typescript
async list(indexName: string, searchParameters: any): Promise<any> {
  const response = await this.http.post<any>(
    `${this.apiUrl}/meilisearch/search`,
    {
      indexName: indexName,
      params: searchParameters
    },
    {
      headers: {
        'Authorization': `Bearer ${this.getAuthToken()}`
      }
    }
  ).toPromise();
  
  return response;
}
```

That's it! The backend proxy handles all the Meilisearch communication and returns results in the same format you're already using.

## Testing Checklist

- [ ] Key retrieval works (`GET /v1/meilisearch/key`)
- [ ] Search returns results (`POST /v1/meilisearch/search`)
- [ ] Filters work (facet filters, disjunctive facets)
- [ ] Pagination works
- [ ] Key expiration handling works
- [ ] Error handling works (400, 404, 500)

## Current Data Status

- ✅ **50 candidates** synced to Meilisearch
- ✅ **100 fulltimers** synced to Meilisearch
- ✅ Indexes configured with proper searchable/filterable attributes

## Index Names

- Candidate index: `dev_candidate_public`
- Fulltimer index: `dev_fulltimer_public`

## Notes

- Keys expire after 2 minutes
- Response format is compatible with existing frontend code
- All Algolia dependencies can be removed from frontend
- Meilisearch is internal-only for security

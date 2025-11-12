# Frontend Meilisearch Migration Guide

This guide provides step-by-step instructions for migrating the frontend Angular application from Algolia to Meilisearch.

## Overview

The backend now supports both Algolia and Meilisearch. The migration can be done gradually while maintaining backward compatibility. The backend will return Meilisearch credentials when the `use_meilisearch` config flag is enabled.

## Prerequisites

- Backend has been updated with Meilisearch support
- Meilisearch service is running and accessible
- Backend `use_meilisearch` config flag is set to `true` (when ready to switch)

## Step 1: Update AlgoliaService

**File**: `src/app/providers/logged-in/algolia.service.ts`

### 1.1 Update getKey() Method

The backend now returns a different response format when using Meilisearch:

**Algolia Response (legacy)**:
```typescript
{
  appId: string,
  securedApiKey: string,
  securedApiKeyValidUntil: number | null
}
```

**Meilisearch Response (new)**:
```typescript
{
  host: string,                    // Meilisearch instance URL
  apiKey: string,                  // Temporary search key
  apiKeyValidUntil: number,        // Unix timestamp
  securedApiKey: string,          // Alias for apiKey (backward compat)
  securedApiKeyValidUntil: number, // Alias (backward compat)
  appId: null                      // Not used for Meilisearch
}
```

**Updated getKey() method**:
```typescript
async getKey(isExpired: boolean = false): Promise<any> {
  if (this.cachedKey && !isExpired && this.cachedKey.securedApiKeyValidUntil && 
      Date.now() / 1000 < this.cachedKey.securedApiKeyValidUntil) {
    return this.cachedKey;
  }

  try {
    const response = await this.http.get<any>(`${this.apiUrl}/algolia/key`).toPromise();
    
    // Handle both Algolia and Meilisearch responses
    const keyData = {
      // Meilisearch fields
      host: response.host || null,
      apiKey: response.apiKey || response.securedApiKey,
      apiKeyValidUntil: response.apiKeyValidUntil || response.securedApiKeyValidUntil,
      // Algolia fields (for backward compatibility)
      appId: response.appId,
      securedApiKey: response.securedApiKey || response.apiKey,
      securedApiKeyValidUntil: response.securedApiKeyValidUntil || response.apiKeyValidUntil,
      // Flag to identify Meilisearch
      isMeilisearch: !!response.host
    };
    
    this.cachedKey = keyData;
    return keyData;
  } catch (error) {
    if (error.status === 400) {
      // Key expired, try again
      return this.getKey(true);
    }
    throw error;
  }
}
```

### 1.2 Update Search Client Initialization

**Option A: Use Proxy Endpoint (Recommended)**

If using the backend proxy endpoint, update the `list()` method:

```typescript
async list(indexName: string, searchParameters: any): Promise<any> {
  const keyData = await this.getKey();
  
  // Check if using Meilisearch
  if (keyData.isMeilisearch) {
    // Use backend proxy endpoint
    const response = await this.http.post<any>(
      `${this.apiUrl}/meilisearch/search`,
      {
        indexName: indexName,
        params: searchParameters
      }
    ).toPromise();
    
    return response;
  } else {
    // Use existing Algolia client
    // ... existing Algolia code ...
  }
}
```

**Option B: Use Direct Meilisearch Client**

1. Install Meilisearch client:
```bash
npm install meilisearch
```

2. Update service to use Meilisearch client:

```typescript
import { MeiliSearch } from 'meilisearch';

// In your service class
private meilisearchClient: MeiliSearch | null = null;

async initializeMeilisearchClient(keyData: any): Promise<void> {
  if (!this.meilisearchClient || this.meilisearchClient.config.host !== keyData.host) {
    this.meilisearchClient = new MeiliSearch({
      host: keyData.host,
      apiKey: keyData.apiKey
    });
  }
}

async list(indexName: string, searchParameters: any): Promise<any> {
  const keyData = await this.getKey();
  
  if (keyData.isMeilisearch) {
    await this.initializeMeilisearchClient(keyData);
    
    // Map Algolia parameters to Meilisearch format
    const meiliParams = this.mapToMeilisearchParams(searchParameters);
    
    const index = this.meilisearchClient!.index(indexName);
    const result = await index.search(
      searchParameters.query || '',
      meiliParams
    );
    
    // Transform Meilisearch response to Algolia format
    return this.mapFromMeilisearchResponse(result, searchParameters);
  } else {
    // Existing Algolia code
  }
}
```

### 1.3 Parameter Mapping Helper Methods

Add these helper methods to map between Algolia and Meilisearch formats:

```typescript
/**
 * Map Algolia search parameters to Meilisearch format
 */
private mapToMeilisearchParams(params: any): any {
  const meiliParams: any = {};
  
  // Pagination
  if (params.page !== undefined && params.hitsPerPage !== undefined) {
    meiliParams.offset = params.page * params.hitsPerPage;
    meiliParams.limit = params.hitsPerPage;
  }
  
  // Filters - Meilisearch uses a different filter syntax
  const filters: string[] = [];
  
  // Facet filters (AND logic)
  if (params.facetFilters && Array.isArray(params.facetFilters)) {
    params.facetFilters.forEach((filter: any) => {
      if (Array.isArray(filter)) {
        // OR within this group
        const orFilters = filter.map(f => this.parseFilter(f));
        if (orFilters.length > 1) {
          filters.push('(' + orFilters.join(' OR ') + ')');
        } else {
          filters.push(orFilters[0]);
        }
      } else {
        filters.push(this.parseFilter(filter));
      }
    });
  }
  
  // Disjunctive facets (OR logic across different facets)
  if (params.disjunctiveFacetsRefinements) {
    Object.keys(params.disjunctiveFacetsRefinements).forEach(facet => {
      const values = params.disjunctiveFacetsRefinements[facet];
      if (Array.isArray(values) && values.length > 0) {
        const orFilters = values.map(v => `${facet} = "${v}"`);
        if (orFilters.length > 1) {
          filters.push('(' + orFilters.join(' OR ') + ')');
        } else {
          filters.push(orFilters[0]);
        }
      }
    });
  }
  
  // Numeric refinements
  if (params.numericRefinements) {
    Object.keys(params.numericRefinements).forEach(attribute => {
      const operators = params.numericRefinements[attribute];
      Object.keys(operators).forEach(op => {
        filters.push(`${attribute} ${op} ${operators[op]}`);
      });
    });
  }
  
  // Tag refinements
  if (params.tagRefinements && Array.isArray(params.tagRefinements)) {
    params.tagRefinements.forEach(tag => {
      filters.push(`_tags = "${tag}"`);
    });
  }
  
  if (filters.length > 0) {
    meiliParams.filter = filters.join(' AND ');
  }
  
  // Attributes to retrieve
  if (params.attributesToRetrieve) {
    meiliParams.attributesToRetrieve = params.attributesToRetrieve;
  }
  
  return meiliParams;
}

/**
 * Parse filter string into Meilisearch format
 */
private parseFilter(filter: string): string {
  // Handle formats like "field:value" or "field=value"
  if (filter.includes(':')) {
    const [field, value] = filter.split(':');
    return `${field.trim()} = "${value.trim()}"`;
  }
  return filter;
}

/**
 * Transform Meilisearch response to Algolia format
 */
private mapFromMeilisearchResponse(result: any, originalParams: any): any {
  const hitsPerPage = originalParams.hitsPerPage || 20;
  const page = originalParams.page || 0;
  
  // Convert id back to objectID for compatibility
  const hits = result.hits.map((hit: any) => {
    if (hit.id && !hit.objectID) {
      hit.objectID = hit.id;
    }
    return hit;
  });
  
  const nbHits = result.estimatedTotalHits || hits.length;
  const nbPages = Math.ceil(nbHits / hitsPerPage);
  const processingTimeMS = result.processingTimeMs || 0;
  
  return {
    results: [{
      hits: hits,
      nbHits: nbHits,
      nbPages: nbPages,
      page: page,
      processingTimeMS: processingTimeMS,
      query: originalParams.query || ''
    }]
  };
}
```

## Step 2: Update Environment Configuration

**Files**: `src/environments/environment.*.ts`

Add Meilisearch configuration (optional, if using direct client):

```typescript
export const environment = {
  // ... existing config ...
  
  meilisearch: {
    // Will be provided by backend via /algolia/key endpoint
    // No need to configure here if using proxy endpoint
  },
  
  // Keep Algolia config during transition
  algolia: {
    // ... existing config ...
  }
};
```

## Step 3: Testing Checklist

Test all search functionality after migration:

### 3.1 Basic Search
- [ ] Text search works correctly
- [ ] Empty query returns all results
- [ ] Special characters are handled properly
- [ ] Search is case-insensitive

### 3.2 Filtering
- [ ] Facet filters (AND logic) work correctly
- [ ] Disjunctive facets (OR logic) work correctly
- [ ] Multiple filters can be combined
- [ ] Numeric range filters work
- [ ] Tag refinements work

### 3.3 Pagination
- [ ] First page loads correctly
- [ ] Can navigate to next/previous pages
- [ ] Page numbers are correct
- [ ] Total hits count is accurate
- [ ] Infinite scroll works (if used)

### 3.4 Key Management
- [ ] Keys are retrieved successfully
- [ ] Expired keys trigger refresh
- [ ] Keys are cached appropriately
- [ ] 400 error on expired key is handled

### 3.5 Error Handling
- [ ] Network errors are handled gracefully
- [ ] 404 errors (index not found) are handled
- [ ] 500 errors (server errors) are handled
- [ ] Timeout errors are handled

### 3.6 Performance
- [ ] Search response time is acceptable (< 200ms)
- [ ] No memory leaks in search client
- [ ] SSR caching works correctly (TransferState)

## Step 4: Gradual Migration Strategy

### Phase 1: Dual Support
1. Update `AlgoliaService` to support both Algolia and Meilisearch
2. Detect which service to use based on backend response
3. Test with Meilisearch in development environment
4. Keep Algolia as fallback

### Phase 2: Testing
1. Enable Meilisearch for a subset of users (feature flag)
2. Monitor error rates and performance
3. Compare search results between Algolia and Meilisearch
4. Fix any issues found

### Phase 3: Full Migration
1. Enable Meilisearch for all users
2. Monitor for 24-48 hours
3. Remove Algolia fallback code once stable
4. Clean up unused Algolia dependencies

## Step 5: Rollback Plan

If issues are encountered:

1. **Immediate Rollback**: Set backend `use_meilisearch` config to `false`
2. **Code Rollback**: Revert frontend changes if needed
3. **Data Verification**: Ensure Algolia indexes are still in sync

## Common Issues and Solutions

### Issue: Filters not working correctly
**Solution**: Verify filter syntax matches Meilisearch format. Check backend logs for filter parsing errors.

### Issue: Pagination shows wrong page numbers
**Solution**: Verify offset/limit calculation. Meilisearch uses offset-based pagination, not page-based.

### Issue: Search results differ from Algolia
**Solution**: 
- Check index settings (searchable attributes, ranking rules)
- Verify data sync between Algolia and Meilisearch
- Compare filter logic

### Issue: Keys expire too quickly
**Solution**: Backend generates keys with 2-minute TTL. Frontend should refresh keys before expiration.

## API Reference

### Backend Endpoints

**GET /v1/algolia/key**
- Returns search credentials (Algolia or Meilisearch based on config)
- Requires authentication
- Response format varies based on service

**POST /v1/meilisearch/search** (if using proxy)
- Proxies search requests to Meilisearch
- Accepts Algolia-compatible request format
- Returns Algolia-compatible response format

### Request Format (Proxy Endpoint)

```typescript
{
  indexName: string,
  params: {
    query: string,
    page: number,
    hitsPerPage: number,
    facetFilters?: string[][],
    disjunctiveFacetsRefinements?: { [key: string]: string[] },
    numericRefinements?: { [key: string]: { [operator: string]: number } },
    tagRefinements?: string[]
  }
}
```

### Response Format

```typescript
{
  results: [{
    hits: any[],
    nbHits: number,
    nbPages: number,
    page: number,
    processingTimeMS: number,
    query: string
  }]
}
```

## Support

For issues or questions:
1. Check backend logs for Meilisearch errors
2. Verify Meilisearch service is running
3. Check index configuration matches expected schema
4. Contact backend team for configuration issues

## Notes

- Index names remain the same: `dev_candidate_public`, `dev_fulltimer_public`, etc.
- Data structure is identical between Algolia and Meilisearch
- Frontend can gradually migrate while backend supports both
- Meilisearch response times should be comparable to Algolia (< 100ms typical)


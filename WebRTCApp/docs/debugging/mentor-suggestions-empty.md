# Mentor suggestions appeared empty on Student Dashboard

Date: 2025-11-08
Status: Resolved

## Summary
On the Student dashboard, the mentor suggestions list rendered empty ([]) even when the student's profile was 100% complete and there were mentors in the database that matched the student's areas of interest.

## Impact
- Users with complete profiles saw a friendly empty state instead of mentor recommendations.
- Debug logs for `getMentorSuggestions()` never appeared, making it look like a backend query issue.

## Root Cause
Inertia lazy props do not load on initial hard refresh. The controller returned:

```php
return Inertia::render('Student/Dashboard/Index', [
    'mentorSuggestions' => Inertia::lazy(fn () => $this->getMentorSuggestions()),
]);
```

However, on a direct page load (full refresh), Inertia does not request lazy props unless the client explicitly asks for them via the `X-Inertia-Partial-Data` header. Result: the server never executed `getMentorSuggestions()`, and the prop was absent from `data-page`.

This was visible in the HTML payload: no `mentorSuggestions` key in the Inertia `props`.

## Contributing Factors
- Frontend was not issuing a partial reload for `mentorSuggestions` on mount.
- The presence of other fixes (profile completeness, naming normalization) initially masked the real root cause.
- Docker volume IO on Windows made responses slower, complicating manual observation.

## Fix
Switched `mentorSuggestions` from lazy to eager loading so it is always present on the first render.

File: `app/Http/Controllers/Student/StudentController.php`

- Before:

```php
'mentorSuggestions' => Inertia::lazy(fn () => $this->getMentorSuggestions()),
```

- After:

```php
$mentorSuggestions = $this->getMentorSuggestions();
return Inertia::render('Student/Dashboard/Index', [
    'mentorSuggestions' => $mentorSuggestions,
]);
```

Also re-enabled the certificate verification guard (student must have `certificate_verified = true`).

## Verification
- Confirmed that `data-page` now includes `mentorSuggestions` on first load.
- `php artisan tinker` showed there are mentors available and 5 mentors mapped to the student's Frontend area (id=1).
- UI renders mentor cards instead of the empty state.

## Prevention Guidelines
1. Avoid lazy props for data that must be present on the very first page load.
2. If using lazy props, ensure the frontend requests them via a partial reload on mount (e.g., Inertia `visit` with `only: ['mentorSuggestions']`).
3. Add backend logs which confirm when lazy callbacks are executed to detect silent non-execution.
4. During debugging, inspect the HTML `data-page` to verify which props are actually present.
5. Keep a small health checklist for Inertia pages:
   - Does the first render require this prop? If yes, eager-load it.
   - Are prop names consistent (snake_case vs camelCase) between backend and frontend? Normalize if needed.
   - Are validation gates (e.g., `certificate_verified`) correctly handled with a clear fallback payload?

## Related Improvements
- Frontend normalization to accept both `areasInteres` and `areas_interes`.
- Improved empty state copy to avoid blaming users for incomplete profiles.
- Profile completeness middleware hardened and cached.
- RoadRunner/OPcache/Octane optimizations applied.

## Appendix: Optional Client-side Lazy Load Pattern
If you ever want to keep `Inertia::lazy()`, have the page request it on mount (example pseudo-code):

```js
import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function Index() {
  const { props } = usePage();
  useEffect(() => {
    if (props.mentorSuggestions === undefined) {
      router.reload({
        only: ['mentorSuggestions'],
        preserveScroll: true,
        preserveState: true,
      });
    }
  }, []);
}
```

This ensures the lazy prop is pulled right after the first paint.

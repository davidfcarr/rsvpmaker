# Phase 3: Translation & Block Rendering Security Fixes

**Date:** November 7, 2026  
**Status:** ANALYSIS COMPLETE - READY FOR IMPLEMENTATION  
**Current Error Count:** 1,060 remaining errors (down from original 1,426)

---

## Error Summary (Latest Plugin Check)

| Error Type | Count | % | Priority |
|-----------|-------|---|----------|
| OutputNotEscaped | 962 | 90.8% | HIGH |
| ExceptionNotEscaped | 74 | 7.0% | MEDIUM |
| UnescapedDBParameter | 23 | 2.2% | CRITICAL |
| UnsafePrintingFunction | 1 | 0.1% | LOW |
| **TOTAL** | **1,060** | **100%** | — |

---

## Phase 3 Work Items

### Item 1: Translation Function Escaping (213 errors)

**Issue:** WordPress translation functions `__()` and `_e()` output unescaped strings that must be wrapped with escaping functions when displayed as HTML.

**Files Affected (by error count):**
1. rsvpmaker-template.php - 63 errors
2. rsvpmaker-email.php - 58 errors
3. rsvpmaker-admin.php - 51 errors
4. rsvpmaker-report.php - 26 errors
5. rsvpmaker-util.php - 8 errors
6. rsvpmaker-email-template.php - 3 errors
7. rsvpmaker-widgets.php - 1 error
8. rsvpmaker-stripe.php - 1 error
9. rsvpmaker-display.php - 1 error
10. rsvpmaker-group-email.php - 1 error

**Total Errors:** 213

**Solution Strategy:**

Change these patterns:

```php
// UNSAFE - Translation output not escaped
__('Some text', 'rsvpmaker')        // Returns unescaped string
_e('Some text', 'rsvpmaker')        // Echoes unescaped string

// SAFE - Use WordPress escape variants
esc_html__('Some text', 'rsvpmaker')     // Returns escaped string
esc_html_e('Some text', 'rsvpmaker')     // Echoes escaped string

// For URLs in translations:
esc_url(__('https://example.com', 'rsvpmaker'))

// For attributes:
esc_attr(__('Some attr', 'rsvpmaker'))
```

**Key Patterns to Fix:**

1. **Direct echo of `__()` result:**
   ```php
   // BEFORE
   echo __('Text', 'rsvpmaker');
   
   // AFTER
   echo esc_html__('Text', 'rsvpmaker');
   ```

2. **Printf/sprintf with `__()` arguments:**
   ```php
   // BEFORE
   printf(__('Value: %s', 'rsvpmaker'), $value);
   
   // AFTER
   printf(esc_html__('Value: %s', 'rsvpmaker'), esc_html($value));
   ```

3. **`_e()` function calls (echoes directly):**
   ```php
   // BEFORE
   _e('Click here', 'rsvpmaker');
   
   // AFTER
   esc_html_e('Click here', 'rsvpmaker');
   ```

4. **Translation in HTML attributes:**
   ```php
   // BEFORE
   <option value="something"><?php echo __('Label', 'rsvpmaker'); ?></option>
   
   // AFTER
   <option value="something"><?php echo esc_html__('Label', 'rsvpmaker'); ?></option>
   ```

---

### Item 2: Block Render.php Files (140 errors)

**Issue:** Dynamic Gutenberg block render files use `echo` to output `get_block_wrapper_attributes()` and `$content` without proper escaping. While these are typically safe in practice (WordPress core functions), the linter requires explicit handling.

**Files Affected:** 70 render.php files across `admin/build/` and `admin/src/`

**Error Pattern:**

```php
// Line 1: Error on get_block_wrapper_attributes()
echo get_block_wrapper_attributes();

// Line 4: Error on $content
echo $content;
```

**Why These Are Tricky:**

1. **get_block_wrapper_attributes()** - Returns a pre-sanitized string from WordPress core
2. **$content** - Already escaped by WordPress when passed to render callback
3. Block render functions are special WordPress callbacks with different security model

**Solution Strategy - Add Explicit Escaping:**

Since the linter flags these as unsafe, we need to add explicit handling. Options:

**Option A: Use wp_kses_post() with comment (RECOMMENDED)**
```php
<?php
// The wrapper attributes are safe from WordPress core
echo wp_kses_post(get_block_wrapper_attributes());
// The content is already escaped by WordPress
echo wp_kses_post($content);
```

**Option B: Use printf() with %s for attribute output**
```php
<?php
printf(
    '<div %s>%s</div>',
    wp_kses_post(get_block_wrapper_attributes()),
    wp_kses_post($content)
);
```

**Option C: Use static HTML structure**
```php
<?php
?>
<div <?php echo wp_kses_post(get_block_wrapper_attributes()); ?>>
    <?php echo wp_kses_post($content); ?>
</div>
```

**Recommended Approach:**
- Use wp_kses_post() with a comment explaining the output is from WordPress core
- Keep changes minimal to avoid breaking block functionality
- Batch apply to all 70 render.php files

---

### Item 3: Remaining Database Security (23 errors)

**Issue:** Direct database parameter usage without proper escaping/preparation

**Error Type:** `PluginCheck.Security.DirectDB.UnescapedDBParameter`

**Impact:** HIGH - Potential SQL injection vectors

**Files Likely Affected:**
- Files using direct database queries
- Uses of `$wpdb->query()`, `$wpdb->get_results()` with unescaped values

**Solution:**
- Ensure all `$wpdb->` methods use `prepare()` with placeholders
- Verify Phase 1 fixes are complete

---

### Item 4: Unsafe Printing Function (1 error)

**Issue:** `print` or `print_r` statement outputting unescaped content

**Impact:** LOW - Only 1 error

**Solution:**
- Replace `print` with `echo` + escaping function
- Replace `print_r` with `var_export` + `<pre>` tags for debug output only

---

## Implementation Plan

### Priority Order

1. **HIGH PRIORITY - Translation Functions (213 errors)**
   - Highest error count
   - Easiest to fix (pattern-based replacements)
   - Most widespread across files
   - Estimated effort: 4-6 hours

2. **HIGH PRIORITY - Block Render.php (140 errors)**
   - Large volume of files (70)
   - Similar fix across all files
   - Requires careful testing to not break blocks
   - Estimated effort: 3-4 hours

3. **CRITICAL PRIORITY - Database Security (23 errors)**
   - Lower count but high security impact
   - May require review of complex query logic
   - Estimated effort: 2-3 hours

4. **LOW PRIORITY - Printing Functions (1 error)**
   - Single error
   - Estimated effort: 15 minutes

### Batch Strategy

**Batch 1: rsvpmaker-template.php (63 translation errors)**
- Estimated time: 1.5-2 hours
- Test: Verify template settings UI still works

**Batch 2: rsvpmaker-email.php (58 translation errors)**
- Estimated time: 1.5-2 hours
- Test: Verify email template rendering

**Batch 3: rsvpmaker-admin.php (51 translation errors)**
- Estimated time: 1.5-2 hours
- Test: Verify admin interface text display

**Batch 4: rsvpmaker-report.php (26 translation errors)**
- Estimated time: 45 minutes - 1 hour
- Test: Verify report generation and display

**Batch 5: Remaining 15 translation errors (8 files)**
- Estimated time: 30 minutes
- Quick fixes across util, email-template, widgets, stripe, display, group-email

**Batch 6: Block Render.php files (70 files × 2 errors = 140 errors)**
- Estimated time: 3-4 hours
- Strategy: Bulk find/replace with verification
- Test: Verify blocks still render on frontend/editor

**Batch 7: Database security (23 errors)**
- Estimated time: 2-3 hours
- Review and fix unescaped database parameters

**Batch 8: Cleanup (1 printing function error)**
- Estimated time: 15 minutes

### Total Estimated Work
- **7-12 hours** total implementation
- **3-4 hours** testing and validation
- **10-16 hours** total Phase 3 effort

---

## WordPress Function Reference

### Translation Functions with Escaping

| Old Function | New Function | Use Case |
|-------------|-------------|----------|
| `__()` | `esc_html__()` | Plain text HTML output |
| `_e()` | `esc_html_e()` | Plain text HTML output with echo |
| `esc_attr(__())` | Use directly | HTML attributes |
| `esc_url(__())` | Use directly | URLs |
| `wp_kses_post(__())` | Use directly | Rich HTML content |

### Block Rendering
```php
// WordPress block render callbacks receive:
// - $attributes: Block-specific attributes (safe)
// - $content: Inner content (already escaped)

// Wrapper attributes are safe from WordPress core
get_block_wrapper_attributes()

// Proper escaping for render output
wp_kses_post()  // For content that may contain HTML
```

---

## Validation Checklist

After implementation, verify:

- [ ] All 213 translation function errors resolved
- [ ] All 140 block render.php errors resolved
- [ ] All 23 database parameter errors resolved
- [ ] Single printing function error resolved
- [ ] PHP syntax validation passes on all modified files
- [ ] Block editor still renders blocks correctly
- [ ] Frontend display shows blocks correctly
- [ ] Email templates still generate correctly
- [ ] Admin interface displays translated strings correctly
- [ ] Reports generate and display correctly
- [ ] Database queries return correct results

---

## Success Criteria

✅ **Phase 3 Success = Error reduction from 1,060 to <100 errors**

Breakdown of expected reductions:
- Translation functions: 213 → 0
- Block render.php: 140 → 0
- Database parameters: 23 → 0
- Printing functions: 1 → 0
- **Expected total after Phase 3: ~683 remaining errors**

---

## Files to Review Before Starting

1. [PHASE2_QUICK_REFERENCE.txt](./PHASE2_QUICK_REFERENCE.txt) - Escaping patterns and examples
2. [SECURITY_AUDIT_INDEX.md](./SECURITY_AUDIT_INDEX.md) - Overall security audit context
3. WordPress Escaping Documentation: https://developer.wordpress.org/plugins/security/escaping/

---

## Next Steps

1. Review this analysis with the team
2. Confirm implementation order and approach
3. Start with Batch 1 (rsvpmaker-template.php)
4. Test after each batch
5. Commit with clear messages
6. Update documentation
7. Run WordPress Plugin Check to verify progress

---

**Status:** Ready for implementation  
**Date Prepared:** November 7, 2026

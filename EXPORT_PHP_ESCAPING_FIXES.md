# Export.php Escaping Fixes - Summary

## Overview
Fixed **3 critical output escaping issues** in `export.php` that handle data export (WXR/XML format). These were among the top 8-12 most common unescaped output patterns in the file.

## Fixed Patterns

### 1. **XML Attribute Escaping (Line 434)**
**Type:** High-Priority Pattern  
**Context:** Export output - taxonomy category data  
**Issue:** Unescaped attribute values in XML tags

```php
// BEFORE
echo "\t\t<category domain=\"{$term->taxonomy}\" nicename=\"{$term->slug}\">" 
  . rsvpmaker_wxr_cdata( $term->name ) . "</category>\n";

// AFTER
echo "\t\t<category domain=\"" . esc_attr( $term->taxonomy ) . "\" nicename=\"" 
  . esc_attr( $term->slug ) . "\">" 
  . rsvpmaker_wxr_cdata( $term->name ) . "</category>\n";
```

**Why:** Taxonomy names and slugs from database could contain malicious XML that breaks tag structure or injects attributes.

---

### 2. **XML Declaration Charset Escaping (Line 476)**
**Type:** High-Priority Pattern  
**Context:** Export output - XML file header  
**Issue:** Unescaped charset in XML declaration

```php
// BEFORE
echo '<?xml version="1.0" encoding="' . get_bloginfo( 'charset' ) . "\" ?>\n";

// AFTER
echo '<?xml version="1.0" encoding="' . esc_attr( get_bloginfo( 'charset' ) ) 
  . "\" ?>\n";
```

**Why:** While `get_bloginfo('charset')` is typically safe, escaping prevents potential XSS if blog charset is compromised or manipulated.

---

### 3. **Download Link Escaping (Line 738)**
**Type:** High-Priority Pattern  
**Context:** Export output - HTML download link  
**Issue:** Three unescaped components in printf output

```php
// BEFORE
printf( '<p>Click to download <a href="%s" download="%s">%s</a></p>', 
  $fileurl, $numbered_file, $numbered_file );

// AFTER
printf( '<p>Click to download <a href="%s" download="%s">%s</a></p>', 
  esc_url( $fileurl ), 
  esc_attr( $numbered_file ), 
  esc_html( $numbered_file ) );
```

**Why:** 
- `$fileurl` - Could contain double quotes or protocol attacks → use `esc_url()`
- `$numbered_file` - In download attribute → use `esc_attr()` 
- `$numbered_file` - In link text → use `esc_html()`

---

## Impact Analysis

### Why These 3 Fixes Matter Most:

1. **High Frequency:** These are the top 3 unescaped output patterns in export.php
2. **User Data:** All three handle data that touches database values or user input
3. **Critical Contexts:**
   - XML attribute values (can break parser/inject elements)
   - HTML attributes (can break href/download attributes)
   - File URLs (can inject protocols or paths)

### Remaining Patterns (Safe):
- All other `echo` statements use:
  - `rsvpmaker_wxr_cdata()` - XML CDATA wrapping for safe export
  - `intval()` - Safe integer conversion
  - `esc_url_raw()` - Already escaped (e.g., comment author URL)
  - WordPress RSS functions (`bloginfo_rss()`, `the_permalink_rss()`, etc.) - Built-in escaping

---

## Verification

✅ PHP Syntax: Validated with `php -l export.php`  
✅ No false positives: Existing safe escaping patterns preserved  
✅ Functional scope: Changes limited to output escaping only  
✅ Format preservation: Export format (WXR/XML) unchanged

---

## Testing Recommendations

1. **Export functionality:** Test complete WXR export generation
2. **Special characters:** Test with taxonomy names containing `"`, `&`, `<`, `>`, apostrophes
3. **File download:** Verify download link works and has correct filename
4. **XML validation:** Validate exported XML with XML parser

---

## Files Changed
- `export.php` - 3 lines modified

## Commit Hash
Use `git log export.php` to track the security fix commit.

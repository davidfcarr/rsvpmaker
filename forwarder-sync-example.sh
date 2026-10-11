#!/usr/bin/env bash
set -euo pipefail

# Example reconciliation script for server-side alias sync.
# This is intentionally provider-agnostic: wire the three provider_* functions
# to your actual Siteground/mail API or local MTA alias command tooling.

MANIFEST_PATH="${1:-}"
STATE_PATH="${2:-/var/tmp/toastmost-forwarder-state.json}"

if [[ -z "${MANIFEST_PATH}" || ! -f "${MANIFEST_PATH}" ]]; then
  echo "Usage: $0 /path/to/aliases-manifest.json [/path/to/state.json]" >&2
  exit 1
fi

require_bin() {
  command -v "$1" >/dev/null 2>&1 || {
    echo "Missing required tool: $1" >&2
    exit 1
  }
}

require_bin jq

provider_list_aliases() {
  # Output JSON object of current provider aliases:
  # {
  #   "alias@domain.tld": ["target1@example.net", "target2@example.net"],
  #   ...
  # }
  # Replace this stub with API/CLI calls.
  echo "{}"
}

provider_set_alias_targets() {
  local alias="$1"
  local targets_json="$2"
  # Replace with provider API update/create call.
  # Must set complete target list to enforce removals as well as additions.
  echo "SET ${alias} -> ${targets_json}"
}

provider_delete_alias() {
  local alias="$1"
  # Replace with provider API delete call.
  echo "DELETE ${alias}"
}

# Determine whether an alias is a domain catch-all.
# Common representations:
# - @domain.tld
# - *@domain.tld
# - catchall@domain.tld (provider-specific)
is_catchall_alias() {
  local alias="$1"
  local domain="$2"
  [[ "${alias}" == "@${domain}" || "${alias}" == "*@${domain}" || "${alias}" == "catchall@${domain}" ]]
}

current_json="$(provider_list_aliases)"

if [[ ! -f "${STATE_PATH}" ]]; then
  echo '{"managed_aliases":[]}' > "${STATE_PATH}"
fi

managed_prev_json="$(jq -c '.managed_aliases // []' "${STATE_PATH}")"
managed_now_json="$(jq -c '.managed_aliases // []' "${MANIFEST_PATH}")"

# Upsert all desired aliases exactly as defined in manifest.
jq -c '.aliases | to_entries[]' "${MANIFEST_PATH}" | while IFS= read -r row; do
  alias_addr="$(jq -r '.key' <<<"${row}")"
  targets_json="$(jq -c '.value | unique | sort' <<<"${row}")"
  provider_set_alias_targets "${alias_addr}" "${targets_json}"
done

# Remove aliases that used to be managed but are no longer managed now,
# EXCEPT protected catch-all aliases for listed domains.
comm -23 \
  <(jq -r '.[]' <<<"${managed_prev_json}" | sort -u) \
  <(jq -r '.[]' <<<"${managed_now_json}" | sort -u) | while IFS= read -r gone_alias; do
  [[ -z "${gone_alias}" ]] && continue

  protect=false
  while IFS= read -r domain; do
    if is_catchall_alias "${gone_alias}" "${domain}"; then
      protect=true
      break
    fi
  done < <(jq -r '.protected_catch_all_domains[]?' "${MANIFEST_PATH}")

  if [[ "${protect}" == true ]]; then
    echo "SKIP protected catch-all alias: ${gone_alias}"
    continue
  fi

  provider_delete_alias "${gone_alias}"
done

# Save current managed list as state for next run.
jq -n --argjson managed "${managed_now_json}" '{managed_aliases:$managed}' > "${STATE_PATH}"

echo "Forwarder reconciliation complete"

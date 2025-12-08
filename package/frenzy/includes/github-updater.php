<?php
/*
 * File: includes/github-updater.php
 * Description: Lightweight GitHub release updater for Frenzy
 * Author: Eric Kowalewski
 * Last Updated: 2025-09-02
 */

if (!defined('ABSPATH')) exit;

// Cache key to avoid hitting the API too often
define('FRENZY_UPDATE_CACHE_KEY', 'frenzy_github_release');
define('FRENZY_UPDATE_CACHE_TTL', 6 * HOUR_IN_SECONDS);

/**
 * Check GitHub for a newer release and add it to the update transient.
 */
add_filter('pre_set_site_transient_update_plugins', function ($transient) {
    if (empty($transient) || !is_object($transient)) {
        return $transient;
    }

    $plugin_basename = plugin_basename(FRENZY_MAIN_FILE);
    $release = frenzy_get_latest_release();
    if (!$release || empty($release['version']) || empty($release['zip_url'])) {
        return $transient;
    }

    // Compare versions (strip leading v if present)
    $remote_version = ltrim($release['version'], 'v');
    if (version_compare($remote_version, FRENZY_VERSION, '<=')) {
        return $transient;
    }

    $transient->response[$plugin_basename] = (object) [
        'slug'        => dirname($plugin_basename),
        'plugin'      => $plugin_basename,
        'new_version' => $remote_version,
        'package'     => $release['zip_url'],
        'url'         => $release['html_url'],
    ];

    return $transient;
});

/**
 * Plugin details modal.
 */
add_filter('plugins_api', function ($res, $action, $args) {
    if (!isset($args->slug) || $args->slug !== dirname(plugin_basename(FRENZY_MAIN_FILE))) {
        return $res;
    }
    $release = frenzy_get_latest_release();
    if (!$release) return $res;

    $res = (object) [
        'name'          => 'Frenzy',
        'slug'          => $args->slug,
        'version'       => ltrim($release['version'] ?? FRENZY_VERSION, 'v'),
        'author'        => '<a href="https://thebeartraxs.com">Eric Kowalewski</a>',
        'homepage'      => 'https://github.com/' . FRENZY_REPO,
        'download_link' => $release['zip_url'] ?? '',
        'trunk'         => $release['zip_url'] ?? '',
        'sections'      => [
            'description' => 'Frenzy mockup helper for WooCommerce.',
            'changelog'   => !empty($release['body']) ? nl2br(esc_html($release['body'])) : 'See GitHub releases.',
        ],
    ];
    return $res;
}, 10, 3);

/**
 * Fetch latest release info from GitHub (cached).
 */
function frenzy_get_latest_release(): ?array {
    $cached = get_site_transient(FRENZY_UPDATE_CACHE_KEY);
    if ($cached) return $cached;

    $api = 'https://api.github.com/repos/' . FRENZY_REPO . '/releases/latest';
    $resp = wp_remote_get($api, [
        'headers' => ['Accept' => 'application/vnd.github.v3+json', 'User-Agent' => 'frenzy-updater'],
        'timeout' => 15,
    ]);

    if (is_wp_error($resp)) return null;
    $code = wp_remote_retrieve_response_code($resp);
    if ($code !== 200) return null;

    $json = json_decode(wp_remote_retrieve_body($resp), true);
    if (!$json || empty($json['tag_name'])) return null;

    $release = [
        'version'  => $json['tag_name'],
        'zip_url'  => $json['zipball_url'] ?? '',
        'html_url' => $json['html_url'] ?? '',
        'body'     => $json['body'] ?? '',
    ];

    set_site_transient(FRENZY_UPDATE_CACHE_KEY, $release, FRENZY_UPDATE_CACHE_TTL);
    return $release;
}

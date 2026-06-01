<?php
// 1. Add rewrite rules for sw.js and manifest.json
function crm_pwa_rewrite_rules() {
    add_rewrite_rule('^sw\.js$', 'index.php?crm_sw=1', 'top');
    add_rewrite_rule('^manifest\.json$', 'index.php?crm_manifest=1', 'top');
    add_rewrite_rule('^manifest-manager\.json$', 'index.php?crm_manifest=manager', 'top');
}
add_action('init', 'crm_pwa_rewrite_rules');

// 2. Add query vars
function crm_pwa_query_vars($vars) {
    $vars[] = 'crm_sw';
    $vars[] = 'crm_manifest';
    return $vars;
}
add_filter('query_vars', 'crm_pwa_query_vars');

// 3. Output the raw files dynamically
function crm_pwa_template_redirect() {
    if (get_query_var('crm_sw')) {
        header('Content-Type: application/javascript');
        header('Cache-Control: no-cache');
        
        ?>
const CACHE_NAME = 'crm-app-v2';
const urlsToCache = [
  '/',
  '<?php echo home_url('/closing-manager/'); ?>',
  '<?php echo get_template_directory_uri(); ?>/css/custom.css',
  '<?php echo get_template_directory_uri(); ?>/js/custom.js'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(urlsToCache))
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.filter(name => name !== CACHE_NAME).map(name => caches.delete(name))
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;
  
  event.respondWith(
    fetch(event.request)
      .then(networkResponse => {
        // Cache dynamic assets dynamically for offline fallback
        if (networkResponse && networkResponse.status === 200) {
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, responseToCache);
          });
        }
        return networkResponse;
      })
      .catch(() => {
        // Fallback to cache if network fails (offline state)
        return caches.match(event.request);
      })
  );
});
        <?php
        exit;
    }

    if (get_query_var('crm_manifest')) {
        header('Content-Type: application/json');
        $manifest_type = get_query_var('crm_manifest');

        if ($manifest_type === 'manager') {
            echo json_encode(array(
                'name' => 'Closing Manager Portal',
                'short_name' => 'Manager CRM',
                'start_url' => home_url('/closing-manager/'),
                'display' => 'standalone',
                'background_color' => '#f8f9fa',
                'theme_color' => '#d4af37',
                'icons' => array(
                    array(
                        'src' => get_template_directory_uri() . '/icon-192.png',
                        'sizes' => '192x192',
                        'type' => 'image/png'
                    ),
                    array(
                        'src' => get_template_directory_uri() . '/icon-512.png',
                        'sizes' => '512x512',
                        'type' => 'image/png'
                    )
                )
            ));
        } else {
            echo json_encode(array(
                'name' => 'CRM Enquiry App',
                'short_name' => 'CRM App',
                'start_url' => home_url('/'),
                'display' => 'standalone',
                'background_color' => '#ffffff',
                'theme_color' => '#2572FC',
                'icons' => array(
                    array(
                        'src' => get_template_directory_uri() . '/icon-192.png',
                        'sizes' => '192x192',
                        'type' => 'image/png'
                    ),
                    array(
                        'src' => get_template_directory_uri() . '/icon-512.png',
                        'sizes' => '512x512',
                        'type' => 'image/png'
                    )
                )
            ));
        }
        exit;
    }
}
add_action('template_redirect', 'crm_pwa_template_redirect');

// 4. Inject tags into wp_head
function crm_pwa_head_tags() {
    if (is_page('closing-manager')) {
        echo '<link rel="manifest" href="' . home_url('/manifest-manager.json') . '">';
        echo '<meta name="theme-color" content="#d4af37">';
    } else {
        echo '<link rel="manifest" href="' . home_url('/manifest.json') . '">';
        echo '<meta name="theme-color" content="#2572FC">';
    }
    echo '<link rel="apple-touch-icon" href="' . get_template_directory_uri() . '/icon-192.png">';
}
add_action('wp_head', 'crm_pwa_head_tags');

// 5. Register Service Worker in footer
function crm_pwa_register_sw() {
    ?>
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('<?php echo home_url('/sw.js'); ?>')
                .then(registration => {
                    console.log('ServiceWorker registered with scope:', registration.scope);
                })
                .catch(err => {
                    console.error('ServiceWorker registration failed:', err);
                });
        });
    }
    </script>
    <?php
}
add_action('wp_footer', 'crm_pwa_register_sw');

// 6. Flush rules once automatically so it works immediately
add_action('init', function() {
    if (!get_option('crm_pwa_rules_flushed_v2')) {
        flush_rewrite_rules();
        update_option('crm_pwa_rules_flushed_v2', 1);
    }
}, 99);

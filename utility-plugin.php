<?php
/*
Plugin Name: Utility Plugin
Description: Plugin che include varie utility, come la disabilitazione dei commenti, la rimozione delle notifiche di aggiornamenti per utenti non amministratori e la rimozione di voci di menu per utenti non admin.
Version: 1.1
Author: Fabio Martini
*/

// Aggiungi la pagina delle impostazioni al menu di amministrazione
function utility_plugin_add_admin_menu() {
    add_options_page(
        'Impostazioni Utility Plugin',
        'Utility Plugin',
        'manage_options',
        'utility_plugin',
        'utility_plugin_options_page'
    );
}
add_action('admin_menu', 'utility_plugin_add_admin_menu');

// Registra le impostazioni
function utility_plugin_settings_init() {
    register_setting('utilityPlugin', 'utility_plugin_settings');

    add_settings_section(
        'utility_plugin_section',
        'Impostazioni delle Utility',
        null,
        'utilityPlugin'
    );

    // Campo per disabilitare i commenti
    add_settings_field(
        'disable_comments',
        'Disabilita Commenti',
        'utility_plugin_disable_comments_render',
        'utilityPlugin',
        'utility_plugin_section'
    );

    // Campo per disabilitare le notifiche di aggiornamento
    add_settings_field(
        'disable_update_notifications',
        'Disabilita Notifiche di Aggiornamento per Utenti Non Admin',
        'utility_plugin_disable_update_notifications_render',
        'utilityPlugin',
        'utility_plugin_section'
    );

    // Campo per nascondere le voci di menu
    add_settings_field(
        'remove_menu_items',
        'Nascondi Voci di Menu per Utenti Non Admin',
        'utility_plugin_remove_menu_items_render',
        'utilityPlugin',
        'utility_plugin_section'
    );
}
add_action('admin_init', 'utility_plugin_settings_init');

// Rendering della checkbox per disabilitare i commenti
function utility_plugin_disable_comments_render() {
    $options = get_option('utility_plugin_settings');
    ?>
    <input type="checkbox" name="utility_plugin_settings[disable_comments]" value="1" <?php checked(1, isset($options['disable_comments']) ? $options['disable_comments'] : 0); ?>>
    <label for="disable_comments">Disabilita commenti nel sito</label>
    <?php
}

// Rendering della checkbox per disabilitare le notifiche di aggiornamento
function utility_plugin_disable_update_notifications_render() {
    $options = get_option('utility_plugin_settings');
    ?>
    <input type="checkbox" name="utility_plugin_settings[disable_update_notifications]" value="1" <?php checked(1, isset($options['disable_update_notifications']) ? $options['disable_update_notifications'] : 0); ?>>
    <label for="disable_update_notifications">Nascondi notifiche di aggiornamento per gli utenti non admin</label>
    <?php
}

// Rendering della checkbox per nascondere le voci di menu
function utility_plugin_remove_menu_items_render() {
    $options = get_option('utility_plugin_settings');
    ?>
    <input type="checkbox" name="utility_plugin_settings[remove_menu_items]" value="1" <?php checked(1, isset($options['remove_menu_items']) ? $options['remove_menu_items'] : 0); ?>>
    <label for="remove_menu_items">Nascondi voci di menu per utenti non admin</label>
    <?php
}

// Crea la pagina delle impostazioni
function utility_plugin_options_page() {
    ?>
    <form action="options.php" method="post">
        <h2>Impostazioni Utility Plugin</h2>
        <?php
        settings_fields('utilityPlugin');
        do_settings_sections('utilityPlugin');
        submit_button();
        ?>
    </form>
    <?php
}

// Controlla se la disabilitazione dei commenti è abilitata
function is_disable_comments_enabled() {
    $options = get_option('utility_plugin_settings');
    return isset($options['disable_comments']) && $options['disable_comments'] == 1;
}

// Controlla se la disabilitazione delle notifiche di aggiornamento è abilitata
function is_disable_update_notifications_enabled() {
    $options = get_option('utility_plugin_settings');
    return isset($options['disable_update_notifications']) && $options['disable_update_notifications'] == 1;
}

// Controlla se la rimozione delle voci di menu è abilitata
function is_remove_menu_items_enabled() {
    $options = get_option('utility_plugin_settings');
    return isset($options['remove_menu_items']) && $options['remove_menu_items'] == 1;
}

// Funzione per disabilitare i commenti
function disable_comments_post_types_support() {
    if (is_disable_comments_enabled()) {
        $post_types = get_post_types();
        foreach ($post_types as $post_type) {
            if (post_type_supports($post_type, 'comments')) {
                remove_post_type_support($post_type, 'comments');
                remove_post_type_support($post_type, 'trackbacks');
            }
        }
    }
}
add_action('admin_init', 'disable_comments_post_types_support');

// Chiudi i commenti sui post esistenti
function disable_comments_status() {
    if (is_disable_comments_enabled()) {
        return false;
    }
    return true;
}
add_filter('comments_open', 'disable_comments_status', 20, 2);
add_filter('pings_open', 'disable_comments_status', 20, 2);

// Nascondi il menu dei commenti dalla bacheca di amministrazione
function disable_comments_admin_menu() {
    if (is_disable_comments_enabled()) {
        remove_menu_page('edit-comments.php');
    }
}
add_action('admin_menu', 'disable_comments_admin_menu');

// Funzione per rimuovere le notifiche di aggiornamento per utenti non admin
function remove_update_notifications() {
    if (!current_user_can('manage_options') && is_disable_update_notifications_enabled()) {
        remove_action('admin_notices', 'update_nag', 3);
        remove_action('admin_notices', 'maintenance_nag', 10);
        add_filter('pre_site_transient_update_core', '__return_null');
        add_filter('pre_site_transient_update_plugins', '__return_null');
        add_filter('pre_site_transient_update_themes', '__return_null');
    }
}
add_action('admin_init', 'remove_update_notifications');

// Funzione per nascondere voci di menu specifiche per utenti non admin
function remove_menus_for_non_admins() {
    if (is_remove_menu_items_enabled() && !current_user_can('manage_options')) {
        remove_menu_page('index.php');       // Dashboard
        remove_menu_page('edit-comments.php'); // Commenti
        remove_menu_page('tools.php');        // Strumenti
    }
}
add_action('admin_menu', 'remove_menus_for_non_admins'); // Usa una priorità alta per eseguire la funzione dopo che i menu sono stati aggiunti

// Aggiungi CSS personalizzato per nascondere la voce di menu
function hide_menu_with_css() {
    if (is_remove_menu_items_enabled() && !current_user_can('manage_options')) { // Verifica che non sia un amministratore
        echo '<style>
            #toplevel_page_vc-welcome {
                display: none !important;
            }
        </style>';
    }
}
add_action('admin_head', 'hide_menu_with_css');




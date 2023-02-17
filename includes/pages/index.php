<div id="bca-jarvis-admin-page">
    <h1 class="bca-jarvis-admin-page-title"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post">
        <?php
        settings_fields( 'marketplace-library-general' ); // output security fields for the registered setting "marketplace_settings"
        do_settings_sections( 'marketplace-library-general' ); // output setting sections and their fields
        submit_button('Save Settings'); // output save settings button
        ?>
    </form>
</div>
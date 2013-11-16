<div class="wrap">
  <?php screen_icon(); ?>
  <h2>Test Settings</h2>
  <form action="options.php" method="post">
    <?php
     settings_fields('Test_Options');
     do_settings_sections('tests');
    ?>
    <div style="margin-top: 18px;">
      <?php submit_button(); ?>
    </div>
    
  </form>
</div>

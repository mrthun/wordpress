<div class="wrap">
  <?php screen_icon(); ?>
  <h2>CT Search Settings</h2>
  <form action="options.php" method="post">
    <?php
     settings_fields('CTsearch_Options');
     do_settings_sections('ctsearch');
    ?>
    <div style="margin-top: 18px;">
      <?php submit_button(); ?>
    </div>
    
  </form>
</div>

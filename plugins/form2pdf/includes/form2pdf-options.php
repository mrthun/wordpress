<div class="wrap">
  <?php screen_icon(); ?>
  <h2>CT Search Settings</h2>
  <form action="options.php" method="post">
    <?php
     settings_fields('Form2pdf_Options');
     do_settings_sections('form2pdf');
    ?>
    <div style="margin-top: 18px;">
      <?php submit_button(); ?>
    </div>
    
  </form>
</div>

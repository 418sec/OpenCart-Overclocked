<?php echo $header; ?>
<div id="content">
  <div class="breadcrumb">
  <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
  <?php } ?>
  </div>
  <?php if ($error_warning) { ?>
    <div class="warning"><?php echo $error_warning; ?></div>
  <?php } ?>
  <?php if ($success) { ?>
    <div class="success"><?php echo $success; ?></div>
  <?php } ?>
  <div class="box">
  <div class="heading">
    <h1><img src="view/image/category.png" alt="" /> <?php echo $heading_title; ?></h1>
    <div class="buttons">
      <a onclick="$('#form').attr('action', '<?php echo $enabled; ?>'); $('#form').submit();" class="button-save"><?php echo $button_enable; ?></a>
	  <a onclick="$('#form').attr('action', '<?php echo $disabled; ?>'); $('#form').submit();" class="button-cancel"><?php echo $button_disable; ?></a>
      <a href="<?php echo $insert; ?>" class="button"><?php echo $button_insert; ?></a>
	  <a href="<?php echo $repair; ?>" class="button-repair"><?php echo $button_repair; ?></a>
      <a onclick="$('#form').submit();" class="button-delete"><?php echo $button_delete; ?></a>
    </div>
  </div>
  <div class="content">
    <?php if ($navigation_hi) { ?>
      <div class="pagination" style="margin-bottom:10px;"><?php echo $pagination; ?></div>
    <?php } ?>
    <form action="<?php echo $delete; ?>" method="post" enctype="multipart/form-data" id="form">
    <table class="list">
      <thead>
        <tr>
          <td width="1" style="text-align:center;"><input type="checkbox" onclick="$('input[name*=\'selected\']').attr('checked', this.checked);" /></td>
          <td class="left"><?php echo $column_id; ?></td>
          <td class="left"><?php echo $column_image; ?></td>
          <td class="left"><?php echo $column_name; ?></td>
          <td class="left"><?php echo $column_sort_order; ?></td>
		  <td class="left"><?php echo $column_status; ?></td>
          <td class="right"><?php echo $column_action; ?></td>
        </tr>
      </thead>
      <tbody>
      <?php if ($categories) { ?>
        <?php foreach ($categories as $category) { ?>
          <tr>
            <td style="text-align:center;"><?php if ($category['selected']) { ?>
              <input type="checkbox" name="selected[]" value="<?php echo $category['category_id']; ?>" checked="checked" />
            <?php } else { ?>
              <input type="checkbox" name="selected[]" value="<?php echo $category['category_id']; ?>" />
            <?php } ?></td>
            <td class="center"><?php echo $category['category_id']; ?></td>
            <td class="center"><img src="<?php echo $category['image']; ?>" alt="<?php echo $category['name']; ?>" style="padding:1px; border:1px solid #DDD;" /></td>
            <td class="left"><?php echo $category['name']; ?></td>
            <td class="center"><?php echo $category['sort_order']; ?></td>
			<?php if ($category['status'] == 1) { ?>
              <td class="center"><span class="enabled"><?php echo $text_enabled; ?></span></td>
            <?php } else { ?>
              <td class="center"><span class="disabled"><?php echo $text_disabled; ?></span></td>
            <?php } ?>
            <td class="right"><?php foreach ($category['action'] as $action) { ?>
              <a href="<?php echo $action['href']; ?>" class="button-form"><?php echo $action['text']; ?></a>
            <?php } ?></td>
          </tr>
        <?php } ?>
      <?php } else { ?>
        <tr>
          <td class="center" colspan="7"><?php echo $text_no_results; ?></td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
    </form>
    <?php if ($navigation_lo) { ?>
      <div class="pagination"><?php echo $pagination; ?></div>
	<?php } ?>
  </div>
  </div>
</div>
<?php echo $footer; ?>
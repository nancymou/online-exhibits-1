<?php
  $item_title = strip_formatting(metadata('item', array('Dublin Core', 'Title')));
  if (empty($item_title)) { $item_title = __('[Untitled]'); }

  echo head(
    array(
      'title'     => $item_title,
      'bodyid'    => 'items',
      'bodyclass' => 'show item'
    )
  );

?>

<div class="sharethis-wrap">
  <div id="sharethis">
    <span>Share this Exhibit!</span>
    <div class="fb-like"
         data-send="false"
         data-layout="button_count"
         data-show-faces="false"
         data-font="arial">
    </div>
    <div class="twitter-share">
      <a  href="https://twitter.com/share"
          class="twitter-share-button"
          data-text="I just saw '<?php echo metadata('exhibit','title',array('no_escape' => true)); ?>' at the MLibary Online Exhibits!" >
        Tweet
      </a>
    </div>
  </div>
</div>

<?php
  if (isset($_GET['exhibit']) && isset($_GET['page'])) {
    echo '<div class="button exhibit-item-back-button"><a href="' .
        exhibit_builder_exhibit_uri(
          get_record_by_id('exhibit', $_GET['exhibit']),
          get_record_by_id('exhibit_page', $_GET['page'])
        ) .
      '">Return to Exhibit</a></div>';
  }
?>

<h1><?php echo $item_title; ?></h1>

<div id="primary">
  <?php
    $image_index = 0;
    $audio_file = false;
    $html_thumnailsize_image = "";
    $html_fullsize_image = "";
    $item_type = (!empty($item->getItemType()->name)) ? $item->getItemType()->name : 'Image';
    $theme_name = (!isset($exhibit->theme)) ? 'mlibrary' : $exhibit->theme;

    $audio = array(
      'application/ogg',
      'audio/aac',
      'audio/aiff',
      'audio/midi',
      'audio/mp3',
      'audio/mp4',
      'audio/mpeg',
      'audio/mpeg3',
      'audio/mpegaudio',
      'audio/mpg',
      'audio/ogg',
      'audio/x-mp3',
      'audio/x-mp4',
      'audio/x-mpeg',
      'audio/x-mpeg3',
      'audio/x-midi',
      'audio/x-mpegaudio',
      'audio/x-mpg',
      'audio/x-ogg',
      'application/octet-stream'
    );

    if ($item_type != 'Video') {
      mlibrary_display_still_image('item', $image_index, $audio, $theme_name);
    }

    if (!$html_fullsize_image && ($audio_file || ($item_type=='Sound'))) {
      echo '<img src="' . img('audio_default02.gif') . '" alt="Oops" /></div>';
    } elseif ($item_type == 'Video') {
      // videos is displayed by using embeded tag from youtube
      echo mlibrary_display_video('item');
    }

    echo mlibrary_metadata_sideinfo('item');

    mlibrary_display_related_exhibits('item');
  ?>

  <div id="item-metadata">
    <?php
      // The following function prints all the the metadata associated with an item: Dublin Core, extra element sets, etc.
      // See http://omeka.org/codex or the examples on items/browse for information on how to print only select metadata fields.
      echo all_element_texts('item');
    ?>
  </div> <!--item-metadata-->

  <?php fire_plugin_hook('public_items_show', array('view' => $this, 'item' => $item)); ?>

</div> <!--// end primary-->

<script type="text/javascript">
  jQuery.noConflict();
  jQuery(document).ready(function() {
    jQuery("#showcase").awShowcase({
      width:                650,
      height:               450,
      auto:                 true,
      interval:             6500,
      continuous:           true,
      loading:              true,
      tooltip_width:        200,
      tooltip_icon_width:   32,
      tooltip_icon_height:  32,
      tooltip_offsetx:      18,
      tooltip_offsety:      0,
      arrows:               false,
      buttons:              true,
      btn_numbers:          true,
      keybord_keys:         true,
      mousetrace:           false,
      pauseonover:          true,
      transition:           'vslide',
      transition_speed:     0,
      show_caption:         'onload',
      thumbnails:           false,
      thumbnails_position:  'outside-last',
      thumbnails_direction: 'horizontal',
      thumbnails_slidex:    0
    });
  });

  jQuery("#item-images").data('curimg','img1');
  jQuery(".square_thumbnail").click(function(e){
    var target = jQuery(this);
    var classList = target.attr('class').split(/\s+/);
    var imgClass = classList[1]; //hardcoded, but simplest way to get to specific class, without regex and loop

    target.siblings(".file-metadata").hide();
    jQuery("#fsize_images div").hide();
    target.siblings(".file-metadata." + imgClass).show();

    var fullsize = target.siblings('#fsize_images').find("." + imgClass);
    if (fullsize.length > 0 && fullsize.hasClass('fullsize')) {
      fullsize.show();
    } else {
//      jQuery("#fsize_images").html(imagesJSON[imgClass]+jQuery("#fsize_images"));
      // question to Wesley why we
      jQuery("#fsize_images").html(imagesJSON[imgClass] + jQuery("#fsize_images").html());
      jQuery("a.fancyitem").fancybox(jQuery(document).data('fboxSettings'));
    }
        // look for imgClass and show it.
  });
</script>

<?php echo foot(); ?>

<?php
use Dompdf\Dompdf;
use Dompdf\Options as DompdfOptions;
use qursus\UserAccess;
use qursus\Module;

list($params, $providers) = eQual::announce([
    'description'   => "Returns a fully loaded JSON formatted single module.",
    'params'        => [
        'id' =>  [
            'description'   => 'Module identifier (id field).',
            'type'          => 'integer',
            'required'      => true
        ],
        'mode' =>  [
            'description'   => 'Rendering mode (pdf or html).',
            'type'          => 'string',
            'default'       => 'pdf'
        ],
        'lang' =>  [
            'description'   => 'Language requested for multilang values.',
            'type'          => 'string',
            'default'       => constant('DEFAULT_LANG')
        ]
    ],
    'response'      => [
        'accept-origin' => '*',
        // 'cacheable'     => true
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

$module = eQual::run('get', 'qursus_module', ['lang' => $params['lang'], 'id' => $params['id']]);


// linearize module
$pages = [];

foreach($module['chapters'] as $chapter) {
    foreach($chapter['pages'] as $page_index => $page) {

        $pages = array_merge($pages, linearizePage($page));

        if($page['sections'] && count($page['sections'])) {
            foreach($page['sections'] as $section) {
                foreach($section['pages'] as $section_page) {
                    $pages = array_merge($pages, linearizePage($section_page));
                }
            }
        }

    }
}

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>qursus</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="<?php echo constant('ROOT_APP_URL'); ?>/app/style_pdf.css">

    <style>
        body {
            overflow-y: scroll;
            background: unset;
        }
    </style>
  </head>
<body>


<div class="container-inner">

<div class="page">
    <div class="title-brand">eQual.run</div>
    <div class="title-pack"><?php echo $module['pack_id']['title']; ?></div>
    <div class="title-module">MODULE <?php echo $module['identifier']; ?></div>
</div>

<?php
foreach($pages as $page) {
    displayPage($page);
}
?>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

// instantiate and use the dompdf class
$options = new DompdfOptions();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$http_context = stream_context_create([
	'ssl' => [
		'verify_peer'       => FALSE,
		'verify_peer_name'  => FALSE,
		'allow_self_signed' => TRUE
	]
]);
$dompdf->setHttpContext($http_context);

$dompdf->setPaper('A4', 'landscape');
$dompdf->loadHtml((string) $html);
$dompdf->render();

$canvas = $dompdf->getCanvas();
$font1 = $dompdf->getFontMetrics()->getFont("Montserrat", "bold");
$font2 = $dompdf->getFontMetrics()->getFont("Montserrat", "bold");
// $canvas->page_text(90, 15, "Help2Protect.info", $font1, 18, array(0.74,0.25,0.57));
$canvas->page_text(95, $canvas->get_height() - 58, "Help2Protect.info", $font2, 11, array(0.074,0.25,0.57));
$canvas->page_text(220, $canvas->get_height() - 58, $module['pack_id']['title'], $font2, 11, array(0,0.066,0.29));
// $canvas->page_text(705, $canvas->get_height() - 45, "p. {PAGE_NUM} / {PAGE_COUNT}", $font2, 11, array(0,0.066,0.29));
$canvas->page_text(650, $canvas->get_height() - 58, "Module {$module['identifier']} - Page {PAGE_NUM} ", $font2, 11, array(0,0.066,0.29));


// get generated PDF raw binary
$output = $dompdf->output();


if($params['mode'] == 'pdf') {
    $context->httpResponse()
    ->header('Content-Type', 'application/pdf')
    // ->header('Content-Disposition', 'attachment; filename="document.pdf"')
    ->header('Content-Disposition', 'inline; filename="document.pdf"')
    ->body($output)
    ->send();

}
else {
    $context->httpResponse()
    ->header('Content-Type', 'text/html')
    ->body($html)
    ->send();
}



function linearizePage($original_page) {
  // one page can result in several PDF pages
  $pages = [];


  $page = json_decode(json_encode($original_page), true);

  // create additional leaves if row_spans exceed 8
  $new_leaves_count = 0;
  foreach($original_page['leaves'] as $leaf_index => $leaf) {

    $new_leaf = [
        'groups'        => [],
        'fixed_groups'  => 0,
        'fixed_rows'    => 0,
        'remaining'     => 8
    ];
    $leaf['remaining'] = 8;

    foreach($leaf['groups'] as $group_index => $group) {

        if($group['fixed']) {
            $new_leaf['groups'][] = json_decode(json_encode($group), true);
            $new_leaf['fixed_groups']++;
            $new_leaf['fixed_rows'] += $group['row_span'];
            $new_leaf['remaining'] -= $group['row_span'];
        }
        $leaf['remaining'] -= $group['row_span'];

        // move exceeding groups to a new leaf
        if($leaf['remaining'] < 0) {
            unset($page['leaves'][$leaf_index+$new_leaves_count]['groups'][$group_index]);

            if($new_leaf['remaining'] >= $group['row_span']) {
                $new_leaf['groups'][] = json_decode(json_encode($group), true);
                $new_leaf['remaining'] -= $group['row_span'];
            }
            else {
                $page['leaves'][] = json_decode(json_encode($new_leaf), true);
                $new_leaf['groups'] = array_slice($new_leaf['groups'], 0, $new_leaf['fixed_groups']);
                $new_leaf['remaining'] = 8 - $new_leaf['fixed_rows'];
            }
        }
    }

    if(count($new_leaf['groups']) > $new_leaf['fixed_groups']) {
      $leaf = json_decode(json_encode($new_leaf), true);

      if($leaf_index == 0) {
        // create a new page with right leave empty
        $new_page = [
          'leaves' => [
              json_decode(json_encode($page['leaves'][0]), true),
              []
          ]
        ];
        $pages[] = $new_page;
        $page['leaves'][0] = $leaf;
      }
      else {
        // we must insert the leaf just after the current $leaf_index (not at the end of the page)
        array_splice($page['leaves'], $leaf_index+$new_leaves_count+1, 0, [$leaf]);
        ++$new_leaves_count;
      }

    }
  }

  // process leaves
  if(count($page['leaves']) > 2) {
    $first_leaf = json_decode(json_encode($page['leaves'][0]), true);

    $new_page = [
        'leaves' => [
            $first_leaf,
            []
        ]
    ];
    // first leaf with no right-leaf
    $pages[] = $new_page;

    for($i = 1, $n = count($page['leaves']); $i < $n; ++$i) {
        $leaf = $page['leaves'][$i];
        // fresh copy
        $first_leaf = json_decode(json_encode($page['leaves'][0]), true);
        foreach($first_leaf['groups'] as $group_index => $group) {
            foreach($group['widgets'] as $widget_index => $widget) {
                if(strpos($widget['type'], 'selector') === 0 && $widget_index == $i-1) {
                    $first_leaf['groups'][$group_index]['widgets'][$widget_index]['selected'] = true;
                }
            }
        }
        $new_page = [
            'leaves' => [
                $first_leaf,
                json_decode(json_encode($leaf), true)
            ]
        ];
        $pages[] = $new_page;

        foreach($leaf['groups'] as $group) {
          foreach($group['widgets'] as $widget_index => $widget) {
            if(in_array($widget['type'], ['image_popup', 'selector_popup']) && $widget['on_click'] == 'image_full()') {
                // add a page with fullpage image
                $new_page = [
                  'leaves' => [
                      [
                          'background_image'   => qursus_module_render_adapt_url($widget['image_url']),
                          // 'background_image'   => 'https://www.help2protect.info/wp-content/uploads/h2p_programs/PDT/module3/widget-fraud-triangle.png',
                          'background_stretch' => true,
                          'background_opacity' => 1,
                          'groups' => [
                              [
                                  'row_span'           => 8
                              ]
                          ]
                      ]
                  ]
                ];
                $pages[] = $new_page;
            }
            else if($widget['type'] == 'tooltip') {

            }
            else {
              continue;
            }
          }
        }


    }
  }
  else {
    $pages[] = $page;
  }

  return $pages;
}

function displayPage($page) {
  echo "<div class=\"page-break\"></div>".PHP_EOL;
  echo "<div class=\"page\">".PHP_EOL;
  $leaves_count = count($page['leaves']);
  $full_page = ($leaves_count < 2)?'full':'';

  $leaf_index = 0;

  foreach($page['leaves'] as $leave) {
    // 1 px transparent
    $background_img = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";
    if(isset($leave['background_image']) && strlen($leave['background_image'])) {
      $background_img = qursus_module_render_adapt_url($leave['background_image']);
    }

    $background_opacity = "0.5";
    if(isset($leave['background_opacity']) && is_numeric($leave['background_opacity'])) {
      $background_opacity = $leave['background_opacity'];
    }

    $background_stretch = "";
    if(isset($leave['background_stretch']) && $leave['background_stretch']) {
      $background_stretch = "stretch";
    }

    $background_add = "";
    if(isset($leave['background_add']) && $leave['background_add'])
    {
      $background_add = "background_add";
    }


    $leaf_right = ($leaf_index && $leaves_count > 1)?'right':'';

    if(!isset($leave['contrast']) || !strlen($leave['contrast'])) {
        $leave['contrast'] = 'light';
    }

    echo "<div class=\"leaf-container {$full_page} {$leaf_right}\">";
    echo "<div class=\"leaf {$full_page} contrast_{$leave['contrast']} widget\"><div class=\"bg-image $background_stretch $background_add\"  style=\"background-image: url($background_img); opacity: $background_opacity;\"></div>".PHP_EOL;

    ++$leaf_index;

    if(!count($leave['groups'])) {
        $leave['groups'][] = [
            'row_span' => 8
        ];
    }
    foreach($leave['groups'] as $group) {

      $direction = "horizontal";

      if(isset($group['direction'])) {
        $direction = $group['direction'];
      }

      echo "<div class=\"group $direction row_span{$group['row_span']}\">".PHP_EOL;

      if(!isset($group['widgets'])) {
        echo "</div>";
        continue;
      }

      $separator = '';

      foreach($group['widgets'] as $widget_index => $widget) {

        if(!isset($widget['content'])) {
          continue;
        }

        $widget['content'] = str_replace('<p></p>', '<p class="line-break">&nbsp;</p>', $widget['content']);
        $content = $widget['content'];
        $style = '';

        switch($widget['type']) {
          case 'chapter_title':
            if($direction == 'horizontal') {
                $style .= "width: ".(int)(100 * (1/ (count($group['widgets'])) ) )."%;";
            }
          case 'chapter_description':
          case 'headline':
          case 'text':
          case 'head_text':
          case 'subtitle':
            $content = $widget['content'];
            if($direction == 'horizontal' && count($group['widgets']) > 1) {
              $style .= "width: ".(int)(100 * (1/ (count($group['widgets'])) ) )."%;";
            }
            break;
          case 'chapter_number':
            if($direction == 'horizontal') {
                $style .= "width: ".(int)(100 * (1/ (count($group['widgets'])) ) )."%;";
            }
            $content = $widget['content'];
            break;
          case 'selector_yes_no':
            if($direction == 'horizontal') {
              $style .= "width: ".(int)(100 * (1/ (count($group['widgets'])) ) )."%;";
            }
            $content = $widget['content'];
            break;
          case 'submit_button':
            // do not show on non-interactive output
            continue 2;
            $content = $widget['content'].' >';
            break;
          case 'selector_wide':
          case 'selector':
            /*
            if($direction == 'horizontal') {
              $style .= "width: ".(int)(100 * (1/ (count($group['widgets'])) ) )."%;";
            }
            */
            $selectorcontent = $widget["content"];
            $counter = $widget_index+1;
            $content = "<span class=\"divider\"></span><span class=\"counter\">{$counter}</span><span class=\"text\">{$selectorcontent}</span><span class=\"marker\"></span><span class=\"selector\"></span>";
            break;
          case 'selector_choice':
            if(strlen($widget['content']) > 155) {
              $style .= "height: 85px;";
            }
            $content = "<span class=\"marker\"></span>".$widget['content'];
            break;
          case 'selector_section':
            $content = $widget['content'];
            // 1 px transparent
            $background_img = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";
            if(isset($widget['image_url']) && strlen($widget['image_url'])) {
              $background_img = qursus_module_render_adapt_url($widget['image_url']);
            }
            $content = "<div class=\"selector-section-container\" style=\"background-image: url($background_img)\"><img src=\"$background_img\" style=\"width: 100%; height: 100%;\"/><div class=\"overlay\"></div><div class=\"text\">$content</div></div> ";
            break;
          case 'selector_section_wide':
            $content = $widget['content'];
            break;
          case 'selector_popup':
            $content = "<div class=\"popup-container\"><span class=\"text\">{$widget["content"]}</span><i class=\"arrow\">&gt;</i></div>";
            break;
          case 'tooltip':
            // do not show non-interactive output
            continue 2;
            $tooltipcontent = $widget["content"];
            $content = "<div class=\"tooltip\">Tip!<span class=\"text\">{$tooltipcontent}</span></div>";
            break;
          case 'sound':
            // do not show non-interactive output
            continue 2;
            $content = "<div class=\"sound-container\"><span>{$widget["content"]}</span><div class=\"play\"></div></div>";
            break;
          case 'video':
            // do not show non-interactive output
            // #todo : convert to PDF link URL
            $content = "<div class=\"video-container\"><a href=\"". qursus_module_render_adapt_url($widget["video_url"]) ."\"><div class=\"play\"></div></a></div>";
            break;
          case 'image_popup':
            $content = "<div class=\"image-container\"><img width=\"100%\" height=\"100%\" src=" . qursus_module_render_adapt_url($widget["image_url"]) . "></div> ";
            break;
          case 'selection':
            $content = "<div><img width=\"100%\" height=\"100%\" src=" . $widget["content"] . "></div> ";
            break;
          case 'first_capital';
            $first = substr(str_replace('<p>', '', $widget['content']), 0, 1);
            $content = $widget['content'];
            $content = "<div class=\"letter\">$first</div><div class=\"text\">".$content."</div>";
            break;
        }

        if(isset($widget['has_separator_right']) && $widget['has_separator_right']) {
          $separator = 'separator_right';
        }
        elseif(isset($widget['has_separator_left']) && $widget['has_separator_left']) {
          $separator = 'separator_left';
        }

        $align = 'none';
        if(isset($widget['align'])) {
          $align = $widget['align'];
        }

        $selected = '';
        if(isset($widget['selected']) && $widget['selected']) {
            $selected = 'selected';
        }
        echo "<div class=\"widget type-{$widget['type']} $selected align-$align\" style=\"$style\">{$content}</div>";

        if($direction == 'horizontal' && $widget_index == 3) {
            echo "<div class=\"line-break\"></div>";
        }

      }
      echo "</div>".PHP_EOL;
      if(strlen($separator)) {
        echo "<div class=\"separator\"><div class=\"$separator\"></div></div>".PHP_EOL;
      }

    }


    echo "</div>".PHP_EOL;
    echo "</div>".PHP_EOL;
  }

  echo "</div>".PHP_EOL;
}



function qursus_module_render_adapt_url($url) {
  if(substr($url, 0, 1) == '/') {
    $url = constant('ROOT_APP_URL').$url;
  }
  return $url;
}
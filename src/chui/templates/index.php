<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<title><?php wp_title( '|', true, 'right' );?></title>
	
    <?php
		wp_head();
	
        switch($device)
        {
            case DeviceType::Android:
            {
                echo "<link rel=\"stylesheet\" href=\"".plugin_dir_url(__FILE__)."css/chui.android-3.0.min.css\">\n";
	            echo "<script type=\"text/javascript\" src=\"".plugin_dir_url(__FILE__)."js/chocolatechip-3.0.min.js\"></script>\n";
                echo "<script type='text/javascript' src='".plugin_dir_url(__FILE__)."js/chui-3.0.min.js'></script>";
                
                break;
            }            
            case DeviceType::iOS:
            {
                echo "<link rel='stylesheet' href='".plugin_dir_url(__FILE__)."css/chui.ios-3.0.min.css'>";
                echo "<script type=\"text/javascript\" src=\"".plugin_dir_url(__FILE__)."js/chocolatechip-3.0.min.js\"></script>\n";
                echo "<script type='text/javascript' src='".plugin_dir_url(__FILE__)."js/chui-3.0.min.js'></script>";
                
                break;
            }
            case DeviceType::WindowsPhone:
            {
	            echo "<link rel='stylesheet' href='".plugin_dir_url(__FILE__)."css/chui.win-3.0.min.css'>";
                echo "<script type=\"text/javascript\" src=\"".plugin_dir_url(__FILE__)."js/chocolatechip-3.0.min.js\"></script>\n";
                echo "<script type='text/javascript' src='".plugin_dir_url(__FILE__)."js/chui-3.0.min.js'></script>";
                
                break;
            }
        }	
		
		$options = get_option('chui_display_options');
			
		$view_model = new ViewModel();
		$view_model->MenuOrder = $options['menu_order'];
		$view_model->IsFrontPage = is_front_page();
		$view_model->FrontPageId = get_option('page_on_front');
		
		$front_page_content = apply_filters('the_content', $post->post_content);
		
		if(! $view_model->IsFrontPage) {
			$main_view_navigation_status = "traversed";
			
			if ($post->post_parent != 0) {
				$view_model->RequestedPage = $post->post_parent;
			}
			else {
				$view_model->RequestedPage = $post->ID;				
			}
			
			$view_model->RequestedPageTitle = $post->post_title;
		}
		else {
			$main_view_navigation_status = "current";
		}
		
		$query = new WP_Query( 'post_type=post' );
										
		//grab the posts
		while ( $query->have_posts() ) :
			$query->the_post();
			
			$blog_post = new BlogPost();
			$blog_post->Title = get_the_title();
			$blog_post->Id = get_the_ID();
			$blog_post->Slug = get_permalink($blog_post->Id);
			//$blog_post->Content = get_the_content();
											
			if(has_excerpt($blog_post->Id)) {
				$blog_post->Excerpt = get_the_excerpt();
			}
			
			array_push($view_model->BlogPosts, $blog_post);
		endwhile;
		
		$args = array(
			'sort_order' => 'ASC',
			'sort_column' => 'post_title',
			'hierarchical' => 1,
			'exclude' => '',
			'include' => '',
			'meta_key' => '',
			'meta_value' => '',
			'authors' => '',
			'child_of' => 0,
			'parent' => -1,
			'exclude_tree' => '',
			'number' => '',
			'offset' => 0,
			'post_type' => 'page',
			'post_status' => 'publish'
		); 
		
		$view_model->Pages = get_pages($args);
	?>		
	
	<style>
		[ui-kind='grouped'] p {
		 margin: 0 0 10px 0;
		}
		blockquote {
		 margin: 10px;
		 font-style: italic;
		}
		blockquote > p, p:last-child {
		 margin: 0;
		}
		ul {
		 list-style-type: disc;
		 margin: 10px 0 10px 20px;
		}
		ol {
		 list-style-type: decimal;
		 margin: 10px 0 10px 25px;
		}
		h2, h3, h4, h5 {
		 margin: 10px 0;
		 font-weight: bold;
		}
		
		img {
			max-width: 100%;
			height: auto;
		}
	</style>
</head>
<body>	
    <nav>
		<h1><?php wp_title( '|', true, 'right' );?></h1>
	</nav>
	<article id="main" class="<?php echo $main_view_navigation_status; ?>">
		<section>
			<?php $view_model->render_menu( $front_page_content ); ?>
		</section>				
	</article>
		
    <nav id="blog-detail-nav">
		<a class='button back'>Back</a>
		<h1>Detail View</h1>					
	</nav>
	<article id="blog-detail" class="next">
		<section id='blog-detail-subview'>
			<div id='blog-detail-contents' style="min-height: 60px;">
						
			</div>
		</section>
	</article>
		
	<?php		
		array_walk($view_model->Pages, "chui_write_views", $view_model);
	?>
        
	<?php
		wp_footer();
	?>
	
	<script  type='text/javascript'>
				
			$(function() {
				var s = setInterval(function() {
					if($.UINavigationHistory) {
												
						clearInterval(s);
						
						var baseUri = '<?php echo parse_url(site_url())['path']; ?>';
						
						if (baseUri.length == 0 || baseUri.substr(baseUri.length-1) != '/')
						{
							baseUri += '/';
						}
						
						$.UITrackHashNavigation(true, baseUri + '#/');
					}
				}, 400);
			});			
			
			$('#Blog section ul li').on('tap', function () {
                
				$('#blog-detail').attr('ui-uri', '/Blog' + $(this).attr('data-blog-path'));
				
				var href = location.href.split('#')[0];
				var path = href + $(this).attr('data-blog-path') + 'json';								
				var content = $('#blog-detail-subview');
				
				$('#blog-detail-nav h1').empty();
				$('#blog-detail-contents').empty();
				$('#blog-detail-contents').UIBusy();
				
				$.ajax({
				   url : path,
				   async: true,
				   success : function(data) {
						var blogPost = JSON.parse(data);						
					
						$('#blog-detail-nav h1').html(blogPost.post_title);
						$('#blog-detail-contents').html(blogPost.post_content);
				   },
				   error: function(data) {
						$('#blog-detail-nav h1').html("Error");
						$('#blog-detail-contents').html("Unable to retrieve blog post at this time");

						if (data.status === -1100) {
						 $('#blog-detail-contents').html("Blog post not found");
						}
				   }
				});				
			});			
			
			<?php							
				/*array_walk($view_model->Pages, "chui_write_route_functions", $view_model);				
				
				function chui_write_route_functions($value, $key, $view_model) {
					$pageId = str_replace(" ", "", $value->post_title);
					$viewId = $pageId;
					
					if($value->ID == $view_model->FrontPageId) {
						$viewId = "main";
					}
					
					echo "var navigate".$pageId." = function () { 				
							console.log('".$pageId." routed to');
							$.UINavigateToView('#".$viewId."');
						};\r\n";
				}*/
				
			?>
			
			var routes = {
			
			<?php			
				/*array_walk($view_model->Pages, "chui_write_routes");
			
				function chui_write_routes($value) {
					$pageId = str_replace(" ", "", $value->post_title);					
					
					echo "'/".$pageId."' : navigate".$pageId . ",\r\n";					
				}*/
			?>

			};
			
			//var router = Router(routes);
			//router.init();
					
	</script>
</body>
</html>
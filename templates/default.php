<section id="sif-instagram-feed">
	<?php if( $settings['hide_title'] ) : ?>
		<h3 class="screen-reader-text"><?php echo $settings['title']; ?></h3>
	<?php else : ?>
		<h3><?php echo $settings['title']; ?></h3>
	<?php endif; ?>

	<div class="sif-media-wrapper">

		<?php foreach( $data->data as $media ) { ?>

			<article class="sif-media">
				<?php 
				if( $media->type == 'video' && $settings['video_player'] ) {

					$attr = '';
					if( $settings['video_player_loop'] ) {
						$attr = ' loop';
					}

					echo '<video src="' . $media->videos->standard_resolution->url . '" poster="' . $media->images->standard_resolution->url . '" controls ' . $attr . '></video>';
				} else {
					$alt = '';
					if( ! $settings['show_description'] ) {
						$alt = $media->caption->text;
					}

					if( ! empty( $links ) ) { 
						echo '<a href="#" target="' . $links . '">';
					}

					echo '<img src="' . $media->images->standard_resolution->url . '" alt="' . $alt . '">';
					
					if( ! empty( $links ) ) {
						echo '</a>';
					}
				}
				?>

				<?php if( $settings['show_likes'] ) : ?>
					<p class="sif-likes">
						<?php echo $media->likes->count; ?>
						<span class="screen-reader-text"><?php _e( 'Like', 'simple-instagram-feed' ); ?></span>
					</p>
				<?php endif; ?>

				<?php if( $settings['show_comments'] ) : ?>
					<p class="sif-comments">
						<?php echo $media->comments->count; ?>
						<span class="screen-reader-text"><?php _e( 'Comments', 'simple-instagram-feed' ); ?></span>
					</p>
				<?php endif; ?>

				<?php if( $settings['show_datetime'] ) : ?>
					<p class="sif-datetime">
						<span class="screen-reader-text"><?php _e( 'Date', 'simple-instagram-feed' ); ?></span>
						<time><?php echo date_i18n( $date_format, $media->caption->created_time ); ?></time>
					</p>
				<?php endif; ?>

				<?php if( $settings['show_description'] ) : ?>
					<p class="sif-description">
						<?php echo $media->caption->text; ?>
					</p>
				<?php endif; ?>

			</article><!-- .sif-media -->

		<?php } ?>

	</div><!-- .sif-media-wrapper -->

</section><!-- .sif-instagram-feed -->
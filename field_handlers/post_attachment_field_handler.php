<?php

function ph_migrate_post_attachment_field_handler_register()
{
	ph_migrate_register_field_handler( 'ph_post_destination','attachment:','ph_migrate_post_attachment_field_handler' );
}
add_action( 'ph_migrate_register_field_handlers','ph_migrate_post_attachment_field_handler_register' );

function ph_migrate_post_attachment_field_handler($post, $fields)
{
	$elem = $fields;
	if ( isset($elem['attachment:path']) && ! is_array( $elem['attachment:path'] ) ) {
		foreach ( $fields as $key => $value ) {
			$elem[ $key ] = array( $value );
		}
	}
	$attachments = array();
	foreach ( $elem as $key => $values ) {
		for ( $i = 0;$i < count( $values );$i++ ) {
			if ( ! isset($attachments[ $i ]) ) {
				$attachments[ $i ] = new Stdclass();
				$attachments[ $i ]->parent = $post['ID'];
			}
			$destkey = substr( $key, strlen( 'attachment:' ) );
			$attachments[ $i ]->{$destkey} = $values[ $i ];
		}
	}

	$destination = new ph_attachment_destination();
	$data = array( 'post_parent' => $post['ID'], 'post_type' => 'attachment' );
	$old_attachments = get_children( $data );
	foreach ( $old_attachments as $id => $obj ) {
		$item = $destination->getItemByID( $id );
		$destination->deleteItem( $item );
	}
	$update_post = false;
	foreach ( $attachments as $attachment ) {
		if ( isset($attachment->path) && $attachment->path != '' && $attachment->path != null && file_exists( $attachment->path ) ) {
			ph_migrate_log("attachment: ".$attachment->path."\n");
			$id = $destination->save( $attachment );
			if(is_object($id))
			{
                ph_migrate_log("saving file failed.\n");
				//saving this item did not work.
				continue;
			}
			if ( 1 == isset($attachment->isTeaser) && $attachment->isTeaser ) {
				set_post_thumbnail( $post['ID'],$id );
			}
			if ( isset($attachment->replaceToken) && $attachment->replaceToken != '' ) {
				$string = $post['post_content'];
				$string = str_replace( $attachment->replaceToken, $id, $string );
				$post['post_content'] = $string;
				$update_post = true;
			}
			if ( isset($attachment->originalURL) && $attachment->originalURL != '' ) {
				$string = $post['post_content'];
				$url = wp_get_attachment_url( $id );
				$post['post_content'] = str_replace( $attachment->originalURL, $url, $string );
				$update_post = true;
			}
			if ( isset($attachment->sourceURL) && $attachment->sourceURL != '' ) {
  			    $url = wp_get_attachment_image_src( $id, 'medium' );
				$string = $post['post_content'];
				$attr = 'src="' .$url[0]. '" width="' .$url[1]. '" height="' . $url[2] . '"';
				$string = str_replace( $attachment->sourceURL, $attr, $string );
				$post['post_content'] = $string;
				$update_post = true;
			}
			if ( isset($attachment->postMeta) && $attachment->postMeta != '' ) {
			    if(is_array($attachment->postMeta)) {
			        foreach($attachment->postMeta as $meta) {
			            update_post_meta($post['ID'],$meta,$id);
                    }
                } else {
                    update_post_meta( $post['ID'],$attachment->postMeta,$id );
                }
            }
		}
	}
	if ( $update_post ) {
		wp_update_post( $post );
	}
}
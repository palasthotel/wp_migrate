<?php

class ph_attachment_destination extends ph_destination
{
	public function createItem()
	{
		return new StdClass();
	}

	public function getItemByID($id)
	{
		$post = new StdClass();
		$post->ID = $id;
		return $post;
	}

	public function save($item)
	{
		$post = array();
		$metas = array();

		global $ph_migrate_field_handlers;
		$field_handlers = array();
		if ( isset($ph_migrate_field_handlers[ get_class( $this ) ]) ) {
			$field_handlers = $ph_migrate_field_handlers[ get_class( $this ) ];
		}
		$postprocess = array();
		foreach ( $item as $property => $value ) {
			$handled = false;
			foreach ( $field_handlers as $key => $callback ) {
				if ( 0 === strpos( $property,$key ) ) {
					$handled = true;
					if ( ! isset($postprocess[ $key ]) ) {
						$postprocess[ $key ] = array( 'callback' => $callback, 'fields' => array() );
					}
					$postprocess[ $key ]['fields'][ $property ] = $value;
				}
			}
			if ( ! $handled ) {
				$post[$property] = $value;
			}
		}

		if ( isset($post['ID']) ) {
			$path = get_attached_file( $post['ID'] );
			copy( $post['path'], $path );
			$attach_data = wp_generate_attachment_metadata( $id,$path );
			wp_update_attachment_metadata( $id,$attach_data );
			$id = $post['ID'];
			ph_migrate_statistics_increment("Attachments updated",1);
		}
		else
		{
			try {
				$magic = new Imagick($post['path']);
				$extension = pathinfo($post['path'],PATHINFO_EXTENSION);
			} catch( Exception $e ) {
				echo 'Exception: '.$e->getMessage();
				$extension = "jpg";
			}
			$filename = basename($post['path']);
			if( $extension == "" || $extension == NULL )
			{
				$info = $magic->identifyImage();
				$compression = $info['compression'];
				$info = pathinfo($post['path']);
				$extension = "";
				if($compression == "JPEG")
				{
					$extension="jpg";
				}
				else if($compression == "PNG")
				{
					$extension="png";
				}
				else if($compression == "GIF")
				{
					$extension="gif";
				}
				$filename = $info['filename'].".".$extension;
			}
			$tmp = tempnam( '/tmp','ph_migrate' );
			$data = file_get_contents( $post['path'] );
			file_put_contents( $tmp, $data );
			$file_array = array(
				'name' => $filename,
				'size' => filesize( $tmp ),
				'tmp_name' => $tmp,
			);
			$id = media_handle_sideload( $file_array,$post['parent'] );
			$path = get_attached_file( $id );
			$attach_data = wp_generate_attachment_metadata( $id,$path );
			wp_update_attachment_metadata( $id,$attach_data );
			$new_post = (array) get_post( $id );
			$post = array_merge( $new_post,$post );
			ph_migrate_statistics_increment("Attachments created",1);
		}
		$mapping = array(
			'title' => 'post_title',
			'caption' => 'post_excerpt',
			'description' => 'post_content',
		);
		foreach ( $mapping as $old => $new ) {
			if ( isset($post[ $old ]) ) {
				$post[ $new ] = $post[ $old ];
			}
		}
		wp_update_post( $post );
		if ( isset($post['alt']) ) {
			add_post_meta( $post['ID'],'_wp_attachment_image_alt',$post['alt'],true );
		}
		foreach ( $postprocess as $key => $dataset ) {
			$callback = $dataset['callback'];
			$callback($post,$dataset['fields']);
		}

		return $id;
	}

	public function deleteItem($item)
	{
		wp_delete_attachment( $item->ID );
	}
}
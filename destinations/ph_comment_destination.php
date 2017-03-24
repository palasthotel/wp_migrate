<?php

class ph_comment_destination extends ph_destination {

    public $comment_fields;

    public function __construct()
    {
        $this->comment_fields = array(
            'ID',
            'comment_post_ID',
            'comment_author',
            'comment_author_email',
            'comment_author_url',
            'comment_content',
            'comment_type',
            'comment_parent',
            'user_id',
            'comment_author_IP',
            'comment_agent',
            'comment_date',
            'comment_approved'
        );
    }

    public function createItem()
    {
        return new Stdclass();
    }

    public function getItemByID($id)
    {
        $data=get_comment($id);
        $data->ID=$id;
        return $data;
    }

    /**
     * get all field handlers
     * @return array
     */
    public function get_field_handlers(){
        return ph_migrate_get_field_handlers($this);
    }

    /**
     * get process list and save unhandled properties to $user global
     * @param $item
     * @param $field_handlers
     *
     * @return array
     */
    public function get_processes($item, $field_handlers, &$rest){
        return ph_migrate_get_process($item, $field_handlers, $rest);
    }

    public function save_comment_data(&$comment) {
        if ( ! isset($comment->ID) ) {
            $commentdata=array();
            foreach($this->comment_fields as $valid) {
                if(!empty($comment->{$valid})) {
                    $commentdata[$valid] = $comment->{$valid};
                }
            }
            $id = wp_insert_comment($commentdata);
            if($id===false) {
                echo 'saving comment failed.\n';
                return;
            }
            $comment->ID=$id;
            $comment->comment_ID=$id;
            ph_migrate_statistics_increment('Comments created',1);
        } else {
            ph_migrate_statistics_increment('Users updated',1);
        }
        wp_update_comment((array)$comment);
    }

    function save_comment_field_handlers($item,$process) {
        $comment=$item;//get_comment($item->ID);
        foreach( $process as $key => $dataset ) {
            $callback = $dataset['callback'];
            $callback($comment,$dataset['fields']);
        }
    }

    public function save($item)
    {
        $core_comment=(object)array();
        $process=$this->get_processes($item,$this->get_field_handlers(),$core_comment);
        $this->save_comment_data($core_comment);
        $item->ID=$core_comment->ID;
        $this->save_comment_field_handlers($item,$process);
        return $item->ID;
    }

    public function deleteItem($item)
    {
        wp_delete_comment($item->ID,true);
    }


}
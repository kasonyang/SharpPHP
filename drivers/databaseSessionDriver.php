<?php
/**
 * 
 * @version 1.0.1
 * @author Kason Yang <kasonyang@163.com>
 */
class databaseSessionDriver implements spSessionDriverInterface {

    /**
     *
     * @var DajectTableObject
     */
    private $table_name;
    private $field_id, $field_data, $field_dateline;

    function getRecord($sessionId) {
        return new DajectRecordObject($this->table_name, array($this->field_id => $sessionId));
    }

    function open($savePath, $sessionName) {

        $path_arr = explode('/', $savePath);
        $field_arr = explode('|', $path_arr[1]);

        $this->table_name = $path_arr[0];
        $this->field_id = $field_arr[0];
        $this->field_data = $field_arr[1];
        $this->field_dateline = $field_arr[2];


        if (!$this->table_name or !$this->field_id or !$this->field_data or !$this->field_dateline) {
            throw new Exception('错误的路径！');
        }
    }

    function close() {
        return TRUE;
    }

    function read($sessionId) {
        return $this->getRecord($sessionId)->{$this->field_data};
    }

    function write($sessionId, $data) {
        $this->getRecord($sessionId)->assign(array(
            $this->field_data => $data,
            'dateline'  =>  time()
        ));
        return TRUE;
    }

    function destroy($sessionId) {
        $this->getRecord($sessionId)->delete();
        return TRUE;
    }

    function gc($lifetime) {
        $tb = new DajectTableObject($this->table_name);
        $min_dateline = time() - $lifetime;
        $tb->where("`{$this->field_dateline}`<{$min_dateline}")->delete();
        return TRUE;
    }

}
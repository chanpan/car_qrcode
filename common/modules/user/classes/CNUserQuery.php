<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\modules\user\classes;
use yii\db\Exception;
use Yii;
/**
 * Description of CNUser
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class CNUserQuery {
    public static function getUser(){
        try{
            return \common\modules\user\models\User::find()->all();
        } catch (Exception $ex) {
            
        }
    }
    public static function getUserById($user_id){
        try{
            return \common\modules\user\models\User::findOne($user_id);
        } catch (Exception $ex) {
             
        }
    }
    public static function saveUser($type, $data){        
        $user = new \common\modules\user\models\User();
        $user->id = isset($data['id']) ? $data['id'] : '';
        $user->username = isset($data['username']) ? $data['username'] : '';
        $user->email = isset($data['email']) ? $data['email'] : '';
        $user->password_hash = isset($data['password_hash']) ? $data['password_hash'] : '';
        $user->auth_key = isset($data['auth_key']) ? $data['auth_key'] : '';
        $user->confirmed_at = isset($data['confirmed_at']) ? $data['confirmed_at'] : '';
        $user->unconfirmed_email = isset($data['unconfirmed_email']) ? $data['unconfirmed_email'] : '';
        $user->blocked_at = isset($data['blocked_at']) ? $data['blocked_at'] : '';
        $user->registration_ip = isset($data['registration_ip']) ? $data['registration_ip'] : '';
        $user->created_at = time();
        $user->updated_at = time();
        $user->flags = 0;
        $user->status = 10;
        $user->last_login_at = isset($data['last_login_at']) ? $data['last_login_at'] : '';
        $user->password_reset_token = isset($data['password_reset_token']) ? $data['password_reset_token'] : '';
        $user->password = Yii::$app->security->generateRandomString(12); 
        if ($user->save()) {
            self::saveProfile($data);
            self::saveUserProject($user->id);
            self::saveRole($data);
            if($type == 'tcc'){
                self::saveUsernCRC($data);
            }
            
            return \backend\modules\manageproject\classes\ChanpanMessage::getSuccess('Save Member success');
        } else {
            return \backend\modules\manageproject\classes\ChanpanMessage::getError(\appxq\sdii\utils\SDUtility::array2String($user->errors));             
        }
    }
    public static function saveProfile($data){
       $profile = \common\modules\user\models\Profile::findOne($data['id']); 
       $profile->name = isset($data['name']) ? $data['name'] : '';
       $profile->public_email = isset($data['email']) ? $data['email'] : '';
       $profile->gravatar_email = isset($data['gravatar_email']) ? $data['gravatar_email'] : '';
       $profile->dob = '00/00/0000';
       $profile->firstname = isset($data['firstname']) ? $data['firstname'] : '';
       $profile->lastname = isset($data['lastname']) ? $data['lastname'] : '';
       $profile->department = isset($data['department']) ? $data['department'] : ''; 
       $profile->position = 0;
       $profile->sitecode = isset($data['sitecode']) ? $data['sitecode'] : ''; //isset(\Yii::$app->user->identity->profile->sitecode) ? \Yii::$app->user->identity->profile->sitecode : '';  
       if ($profile->save()) {
            return \backend\modules\manageproject\classes\ChanpanMessage::getSuccess('Save Profile success');
        } else {
            return \backend\modules\manageproject\classes\ChanpanMessage::getError(\appxq\sdii\utils\SDUtility::array2String($user->errors));   
        }
    }
    public static function saveRole($data){
        $model = new \common\modules\manage_user\models\AuthAssignment();
        $model->user_id = isset($data['id']) ? $data['id'] : '';
        $model->item_name = "author";
        $model->created_at = time();
        if ($model->save()) {
            return \backend\modules\manageproject\classes\ChanpanMessage::getSuccess('Save Profile success');
        } else {
            return \backend\modules\manageproject\classes\ChanpanMessage::getError(\appxq\sdii\utils\SDUtility::array2String($user->errors));   
        }
    }
   
    public static  function saveUserProject($user_id){
        $domain = \backend\modules\manageproject\classes\ChanpanServerConfig::getServerName();
        $data_id = "";
        $sql = "SELECT * FROM dynamic_db WHERE url=:url";
        $dataDbDynamic = \Yii::$app->db_main->createCommand($sql, [':url'=>$domain])->queryOne();
        $data_id = isset($dataDbDynamic['data_id']) ? $dataDbDynamic['data_id'] : '';
        $data = [
            'url'=> \backend\modules\manageproject\classes\ChanpanServerConfig::getServerName(),
            'user_id'=>$user_id,
            'create_by'=> \Yii::$app->user->id,
            'create_at'=>Date('Y-m-d'),
            'data_id'=>$data_id
        ];        
        $dataExecute = \Yii::$app->db_main->createCommand()->insert('user_project', $data)->execute();        
        return $dataExecute;
    }
    
    public static function saveUsernCRC($data){
        try{
            $dataUserAttribute = [
                    'id' => isset($data['id']) ? $data['id'] : '',
                    'username' => $data['username'],
                    'email' => isset($data['email']) ? $data['email'] : " ",
                    'password_hash' => $data['password_hash'],
                    'auth_key' => $data['auth_key'],
                    'confirmed_at' => time(),
                    'created_at' => time(),
                    'updated_at' => time(),
                    'flags' => 0
                ];
            $dataProfileAttribuite = [
                        'user_id' => isset($data['id']) ? $data['id'] : '',
                        'public_email' => isset($data['email']) ? $data['email'] : " ",
                        'tel' => isset($data['telephone']) ? $data['telephone'] : " ",
                        'sitecode' => '00',
                        'firstname' => isset($data['firstname']) ? $data['firstname'] : '',
                        'lastname' => isset($data['lastname']) ? $data['lastname'] : '',
                        'department' => '00',
                        'certificate' => ' ',
                        'position' => 0
                    ];
           $sql="SELECT id FROM user WHERE id=:id";
           $checkUser = \Yii::$app->db_main->createCommand($sql,[':id'=>$$data['id']])->queryOne();
           if(empty($checkUser)){
                \Yii::$app->db_main->createCommand()->insert('user', $dataUserAttribute)->execute();
                \Yii::$app->db_main->createCommand()->insert('profile', $dataProfileAttribuite)->execute(); 

                 $dataRole = [
                     'user_id'=>isset($data['id']) ? $data['id'] : '',
                     'item_name'=>'author',
                     'created_at'=>time()
                 ];
                 \Yii::$app->db_main->createCommand()->insert('auth_assignment', $dataRole)->execute(); 
           }          
   
        } catch (Exception $ex) {
            \backend\modules\ezforms2\classes\EzfFunc::addErrorLog($ex);
        }
    }
}

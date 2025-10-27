<?php
/**
 * Plugin Name: Amelia → Netgsm SMS Webhook (Atomix)
 * Description: Token korumalı /wp-json/atomix/v1/amelia-sms endpoint’i ile Netgsm’e SMS.
 * Version: 1.0.3
 * Author: Atakan Özdal (Atomix)
 */

if (!defined('ABSPATH')) exit;

define('ATX_OPT', 'atomix_netgsm_webhook');
define('ATX_API', 'https://api.netgsm.com.tr/sms/rest/v2/send');

function atx_def(){ return ['token'=>'', 'usercode'=>'','password'=>'','msgheader'=>'','encoding'=>'TR','log'=>1]; }
function atx_get(){ $o=get_option(ATX_OPT,[]); if(!is_array($o)) $o=[]; return array_merge(atx_def(),$o); }
function atx_upd($n){ $m=array_merge(atx_get(),$n); update_option(ATX_OPT,$m); return $m; }

register_activation_hook(__FILE__, function(){
  $s=atx_get(); if(empty($s['token'])){ $s['token']=wp_generate_password(24,false,false); update_option(ATX_OPT,$s); }
});

add_action('admin_menu', function(){
  add_options_page('Atomix Webhook SMS','Atomix Webhook SMS','manage_options','atomix-webhook',function(){
    if(!current_user_can('manage_options')) return; $s=atx_get(); $ok=null;
    if(isset($_POST['save']) && check_admin_referer('save')){
      $usercode=sanitize_text_field($_POST['usercode']??'');
      $password=sanitize_text_field($_POST['password']??'');
      $msgheader=sanitize_text_field($_POST['msgheader']??'');
      $encoding=sanitize_text_field($_POST['encoding']??'TR');
      $log=isset($_POST['log'])?1:0;
      $s=atx_upd(compact('usercode','password','msgheader','encoding','log')); $ok='Ayarlar kaydedildi.';
    }
    if(isset($_POST['regen']) && check_admin_referer('regen')){
      $s['token']=wp_generate_password(24,false,false); update_option(ATX_OPT,$s); $ok='Token yenilendi.';
    }
    $url = site_url('/wp-json/atomix/v1/amelia-sms?token='.($s['token']??''));
    ?>
    <div class="wrap"><h1>Atomix Webhook SMS</h1>
      <?php if($ok): ?><div class="updated"><p><?php echo esc_html($ok);?></p></div><?php endif;?>
      <p><strong>Webhook URL:</strong> <code><?php echo esc_html($url);?></code></p>
      <form method="post"><?php wp_nonce_field('regen');?><button class="button" name="regen" value="1">Token’ı Yenile</button></form>
      <hr><form method="post"><?php wp_nonce_field('save');?>
        <table class="form-table">
          <tr><th>Usercode</th><td><input name="usercode" class="regular-text" value="<?php echo esc_attr($s['usercode']);?>"></td></tr>
          <tr><th>Password</th><td><input name="password" class="regular-text" value="<?php echo esc_attr($s['password']);?>"></td></tr>
          <tr><th>Msgheader</th><td><input name="msgheader" class="regular-text" value="<?php echo esc_attr($s['msgheader']);?>"></td></tr>
          <tr><th>Encoding</th><td>
            <select name="encoding">
              <option value="TR" <?php selected($s['encoding'],'TR');?>>TR</option>
              <option value="TURKCE" <?php selected($s['encoding'],'TURKCE');?>>TURKCE</option>
              <option value="UTF-8" <?php selected($s['encoding'],'UTF-8');?>>UTF-8</option>
            </select>
          </td></tr>
          <tr><th>Debug log</th><td><label><input type="checkbox" name="log" <?php checked($s['log'],1);?>> Açık</label></td></tr>
        </table>
        <p><button class="button button-primary" name="save" value="1">Kaydet</button></p>
      </form></div>
    <?php
  });
});

function atx_norm($p){ $d=preg_replace('/\D+/','',(string)$p); if(strlen($d)==11 && $d[0]==='0') $d='90'.substr($d,1); if(strlen($d)==10) $d='90'.$d; return $d; }

function atx_send($phone,$msg){
  $s=atx_get(); foreach(['usercode','password','msgheader'] as $k){ if(empty($s[$k])) return ['ok'=>false,'code'=>0,'body'=>'missing-credentials']; }
  $payload=['msgheader'=>$s['msgheader'],'messages'=>[['msg'=>(string)$msg,'no'=>atx_norm($phone)]],'encoding'=>$s['encoding']];
  $r=wp_remote_post(ATX_API,[
    'headers'=>['Content-Type'=>'application/json','Authorization'=>'Basic '.base64_encode($s['usercode'].':'.$s['password'])],
    'body'=>wp_json_encode($payload,JSON_UNESCAPED_UNICODE),'timeout'=>30
  ]);
  if(is_wp_error($r)){ if($s['log']) error_log('[Atomix WH] WP_Error: '.$r->get_error_message()); return ['ok'=>false,'code'=>0,'body'=>$r->get_error_message()]; }
  $code=(int)wp_remote_retrieve_response_code($r); $body=wp_remote_retrieve_body($r);
  $ok=($code===200 && ($j=json_decode($body,true)) && ($j['code']??'')==='00'); if($s['log']) error_log('[Atomix WH] code='.$code.' body='.$body);
  return ['ok'=>$ok,'code'=>$code,'body'=>$body];
}

add_action('rest_api_init', function(){
  register_rest_route('atomix/v1','/amelia-sms',[
    'methods'=>['POST','GET'],
    'permission_callback'=>'__return_true',
    'callback'=>function(WP_REST_Request $req){
      $s=atx_get(); if($req->get_param('token')!==($s['token']??'')) return new WP_REST_Response(['ok'=>false,'error'=>'forbidden'],403);
      if($req->get_method()==='GET') return new WP_REST_Response(['ok'=>true,'hint'=>'POST JSON bekleniyor'],200);
      $b=json_decode($req->get_body(),true); if(!is_array($b)) $b=[];
      $phone=$req->get_param('phone'); if(!$phone){ $phone=$b['customer']['phone']??($b['appointment']['customer']['phone']??''); }
      if(!$phone) return new WP_REST_Response(['ok'=>false,'error'=>'missing-phone'],400);

      $first=$b['customer']['firstName']??($b['customer']['first_name']??'');
      $last =$b['customer']['lastName'] ??($b['customer']['last_name'] ??'');
      $name = trim($first.' '.$last) ?: 'Müşterimiz';
      $service=$b['service']['name']??($b['appointment']['service']['name']??'');
      $date=$b['appointment']['date']??($b['appointmentDate']??'');
      $time=$b['appointment']['time']??($b['appointmentTime']??'');
      $status=$b['appointment']['status']??($b['status']??'');

      $evt = sanitize_text_field($req->get_param('evt') ?: 'status');
      switch($evt){
        case 'created': $msg="Merhaba $name, $service randevunuz $date $time için oluşturuldu."; break;
        case 'rescheduled': $msg="Merhaba $name, $service randevunuz $date $time tarihine güncellendi."; break;
        case 'canceled': $msg="Merhaba $name, $service randevunuz $date $time iptal edilmiştir."; break;
        default: $st=$status? " ($status)" : ''; $msg="Merhaba $name, $service randevunuz $date $time için durum güncellendi$st.";
      }

      $res=atx_send($phone,$msg); return new WP_REST_Response(['ok'=>$res['ok'],'http'=>$res['code'],'resp'=>$res['body']], $res['ok']?200:500);
    }
  ]);
});

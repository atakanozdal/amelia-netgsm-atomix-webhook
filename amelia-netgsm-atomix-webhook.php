<?php
/**
 * Plugin Name: Amelia → Netgsm SMS Webhook (Atomix)
 * Description: Token korumalı /wp-json/atomix/v1/amelia-sms endpoint’i ile Netgsm’e SMS.
 * Version: 1.0.4
 * Author: Atakan Özdal (Atomix)
 */

if (!defined('ABSPATH')) exit;

define('ATX_OPT', 'atomix_netgsm_webhook');
define('ATX_API', 'https://api.netgsm.com.tr/sms/rest/v2/send');

function atx_def(){ return ['token'=>'', 'usercode'=>'','password'=>'','msgheader'=>'','encoding'=>'TR','log'=>1]; }
function atx_get(){ $o=get_option(ATX_OPT,[]); if(!is_array($o)) $o=[]; return array_merge(atx_def(),$o); }
function atx_upd($n){ $m=array_merge(atx_get(),$n); update_option(ATX_OPT,$m); return $m; }
function atx_encodings(){ return ['TR','TURKCE','UTF-8']; }
function atx_ensure_token(){ $s=atx_get(); if(empty($s['token'])){ $s['token']=wp_generate_password(24,false,false); update_option(ATX_OPT,$s); } return $s; }

register_activation_hook(__FILE__, function(){ atx_ensure_token(); });

add_action('admin_menu', function(){
  add_options_page('Atomix Webhook SMS','Atomix Webhook SMS','manage_options','atomix-webhook',function(){
    if(!current_user_can('manage_options')) return; $s=atx_ensure_token(); $ok=null;
    if(isset($_POST['save']) && check_admin_referer('atomix_webhook_save')){
      $usercode=sanitize_text_field(wp_unslash($_POST['usercode']??''));
      $password=trim((string)wp_unslash($_POST['password']??''));
      $msgheader=sanitize_text_field(wp_unslash($_POST['msgheader']??''));
      $encoding=sanitize_text_field(wp_unslash($_POST['encoding']??'TR'));
      if(!in_array($encoding,atx_encodings(),true)) $encoding='TR';
      $log=isset($_POST['log'])?1:0;
      $new=compact('usercode','msgheader','encoding','log');
      if($password!=='') $new['password']=$password; // boş gelirse kayıtlı şifre korunur
      $s=atx_upd($new); $ok='Ayarlar kaydedildi.';
    }
    if(isset($_POST['regen']) && check_admin_referer('atomix_webhook_regen')){
      $s['token']=wp_generate_password(24,false,false); update_option(ATX_OPT,$s); $ok='Token yenilendi.';
    }
    $base = rest_url('atomix/v1/amelia-sms');
    $url = add_query_arg('token', rawurlencode((string)$s['token']), $base);
    ?>
    <div class="wrap"><h1>Atomix Webhook SMS</h1>
      <?php if($ok): ?><div class="updated"><p><?php echo esc_html($ok);?></p></div><?php endif;?>
      <p><strong>Webhook URL:</strong> <code><?php echo esc_html($url);?></code></p>
      <p class="description">Çağıran taraf başlık gönderebiliyorsa token'ı adrese değil <code>X-Atomix-Token</code> başlığına koyun (<code><?php echo esc_html($base);?></code>). Adresler sunucu günlüklerine düşer, başlıklar düşmez. Yalnızca POST kabul edilir.</p>
      <form method="post"><?php wp_nonce_field('atomix_webhook_regen');?><button class="button" name="regen" value="1">Token’ı Yenile</button></form>
      <hr><form method="post"><?php wp_nonce_field('atomix_webhook_save');?>
        <table class="form-table">
          <tr><th>Usercode</th><td><input name="usercode" class="regular-text" value="<?php echo esc_attr($s['usercode']);?>"></td></tr>
          <tr><th>Password</th><td><input type="password" name="password" class="regular-text" autocomplete="new-password" value="" placeholder="<?php echo $s['password']!=='' ? esc_attr('(kayıtlı)') : '';?>"></td></tr>
          <tr><th>Msgheader</th><td><input name="msgheader" class="regular-text" value="<?php echo esc_attr($s['msgheader']);?>"></td></tr>
          <tr><th>Encoding</th><td>
            <select name="encoding">
              <option value="TR" <?php selected($s['encoding'],'TR');?>>TR</option>
              <option value="TURKCE" <?php selected($s['encoding'],'TURKCE');?>>TURKCE</option>
              <option value="UTF-8" <?php selected($s['encoding'],'UTF-8');?>>UTF-8</option>
            </select>
          </td></tr>
          <tr><th>Debug log</th><td><label><input type="checkbox" name="log" <?php checked($s['log'],1);?>> Açık</label> <span class="description">(yalnızca WP_DEBUG açıkken yazar)</span></td></tr>
        </table>
        <p><button class="button button-primary" name="save" value="1">Kaydet</button></p>
      </form></div>
    <?php
  });
});

function atx_norm($p){ $d=preg_replace('/\D+/','',(string)$p); if(strlen($d)==11 && $d[0]==='0') $d='90'.substr($d,1); if(strlen($d)==10) $d='90'.$d; return $d; }
function atx_mask($d){ $d=(string)$d; $l=strlen($d); return $l>2 ? str_repeat('*',$l-2).substr($d,-2) : str_repeat('*',$l); }
function atx_log($msg){ $s=atx_get(); if(!empty($s['log']) && defined('WP_DEBUG') && WP_DEBUG) error_log('[Atomix WH] '.$msg); }

/** Kayıtlı token boşsa her istek reddedilir. */
function atx_token_ok($stored,$given){
  if(!is_string($stored) || $stored==='') return false;
  if(!is_string($given) || $given==='') return false;
  return hash_equals($stored,$given);
}

/** Token önce X-Atomix-Token başlığından, yoksa token parametresinden okunur. */
function atx_req_token(WP_REST_Request $req){
  $t=$req->get_header('X-Atomix-Token');
  if(!is_string($t) || $t==='') $t=$req->get_param('token');
  return is_string($t) ? trim($t) : '';
}

function atx_send($phone,$msg){
  $s=atx_get(); foreach(['usercode','password','msgheader'] as $k){ if(empty($s[$k])){ atx_log('eksik ayar: '.$k); return ['ok'=>false]; } }
  $enc=in_array($s['encoding'],atx_encodings(),true) ? $s['encoding'] : 'TR';
  $no=atx_norm($phone);
  $payload=['msgheader'=>$s['msgheader'],'messages'=>[['msg'=>(string)$msg,'no'=>$no]],'encoding'=>$enc];
  $r=wp_remote_post(ATX_API,[
    'headers'=>['Content-Type'=>'application/json','Authorization'=>'Basic '.base64_encode($s['usercode'].':'.$s['password'])],
    'body'=>wp_json_encode($payload,JSON_UNESCAPED_UNICODE),'timeout'=>30
  ]);
  if(is_wp_error($r)){ atx_log('WP_Error phone='.atx_mask($no).' '.$r->get_error_message()); return ['ok'=>false]; }
  $code=(int)wp_remote_retrieve_response_code($r); $body=wp_remote_retrieve_body($r);
  $j=json_decode($body,true); $st=(is_array($j) && isset($j['code'])) ? (string)$j['code'] : '-';
  $ok=($code===200 && $st==='00'); atx_log('phone='.atx_mask($no).' http='.$code.' netgsm='.$st);
  return ['ok'=>$ok];
}

add_action('rest_api_init', function(){
  register_rest_route('atomix/v1','/amelia-sms',[
    'methods'=>'POST',
    'permission_callback'=>'__return_true',
    'callback'=>function(WP_REST_Request $req){
      $s=atx_get(); if(!atx_token_ok($s['token'],atx_req_token($req))) return new WP_REST_Response(['ok'=>false,'error'=>'forbidden'],403);
      $b=json_decode($req->get_body(),true); if(!is_array($b)) $b=[];
      $phone=$req->get_param('phone'); if(!is_scalar($phone)) $phone=''; if(!$phone){ $phone=$b['customer']['phone']??($b['appointment']['customer']['phone']??''); }
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

      $res=atx_send($phone,$msg);
      return $res['ok'] ? new WP_REST_Response(['ok'=>true],200) : new WP_REST_Response(['ok'=>false,'error'=>'send_failed'],500);
    }
  ]);
});

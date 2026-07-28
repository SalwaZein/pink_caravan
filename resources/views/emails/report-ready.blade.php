<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:0;background:#F4EEF1;font-family:Arial,Helvetica,sans-serif;color:#2A2230;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F4EEF1;padding:24px 0;">
    <tr><td align="center">
      <table role="presentation" width="520" cellpadding="0" cellspacing="0" style="width:520px;max-width:92%;background:#ffffff;border:1px solid #EFE2EA;border-radius:14px;overflow:hidden;">
        <tr><td style="height:6px;background:#E6017E;"></td></tr>
        <tr><td style="padding:26px 32px 8px;">
          <div style="font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:#E6017E;">Pink Caravan · القافلة الوردية</div>
          <h1 style="font-size:21px;margin:12px 0 4px;color:#2A2230;">Your report is ready</h1>
          <div style="font-size:15px;color:#6B6472;direction:rtl;">تقريرك جاهز للاطّلاع</div>
        </td></tr>
        <tr><td style="padding:8px 32px 4px;font-size:14px;line-height:1.6;color:#453A44;">
          <p style="margin:12px 0;">Dear {{ $name ?? 'patient' }},<br>
          Your Clinical Breast Examination report <strong>{{ $ref }}</strong> is now available. For your privacy, you'll enter your mobile number and a one-time code to open it.</p>
          <p style="margin:12px 0;direction:rtl;">عزيزتنا،<br>
          تقرير الفحص السريري للثدي <strong>{{ $ref }}</strong> أصبح متاحاً. للحفاظ على خصوصيتك، ستُدخلين رقم جوالك ورمز تحقق لمرة واحدة لفتحه.</p>
        </td></tr>
        <tr><td align="center" style="padding:14px 32px 6px;">
          <a href="{{ $link }}" style="display:inline-block;background:#E6017E;color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none;padding:14px 30px;border-radius:10px;">View my report · عرض تقريري</a>
        </td></tr>
        <tr><td style="padding:6px 32px 22px;font-size:12px;color:#9A8F97;text-align:center;line-height:1.5;">
          {{ $link }}
        </td></tr>
        <tr><td style="padding:14px 32px;background:#FAF4F7;border-top:1px solid #F3E7EE;font-size:11.5px;color:#9A8F97;line-height:1.6;">
          This message is confidential and intended only for the named patient.<br>
          <span style="direction:rtl;display:block;">هذه الرسالة سرية ومخصصة للمريضة المذكورة فقط.</span>
          Pink Caravan — Friends of Cancer Patients (FOCP)
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>

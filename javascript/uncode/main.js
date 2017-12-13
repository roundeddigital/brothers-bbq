<!-- Global Site Tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-74229202-1"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments)};
  gtag('js', new Date());

  gtag('config', 'UA-74229202-1');
</script>

<!--
//-------------------------------------
// Google Event Tracking for Contact 7 Forms

/*
Place in uncode: theme options: css & js
*/
 -->

<!-- Google Analytics -->
<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', 'UA-74229202-1', 'auto');
ga('send', 'pageview');
</script>
<!-- End Google Analytics -->

<!--
/*
Contact 7 Form Google Analytics Tracking
*/
 -->
<script>
document.addEventListener( 'wpcf7mailsent', function( event ) {
    // console.log("Form 7 ID = ",event.detail.contactFormId);
    // ADD Contact 7 Form ID's and Google Analytics information here
    // [contact_form_7_id, ga_form_category, ga_form_action, ga_form_label, ga_form_value]

    // header
    // footer
    // catering

    var formIds = [
    [54760,"Catering Form Submission","submit","broomfield"],
    [54766,"Catering Form Submission","submit","brothers-hq"],
    [54763,"Catering Form Submission","submit","capital-hill"],
    [54762,"Catering Form Submission","submit","greenwood-village"],
    [54751,"Catering Form Submission","submit","lakewood"],
    [54764,"Catering Form Submission","submit","monaco-leetsdale"],
    [66761,"Catering Form Submission","submit","holiday-event"]];

    for (var i=0;i<formIds.length;i++) {
      var formId = formIds[i][0];
      console.log("Each Form ID = ",formId);
      if (event.detail.contactFormId == formId){
        var category = formIds[i][1];
        var action = formIds[i][2];
        var label = formIds[i][3];
        console.log("Google Analytics Category = ",category);
        console.log("Google Analytics Action = ",action);
        console.log("Google Analytics Label = ",label);
        ga('send', 'event', category, action, label);
      }
    }
}, false );
jQuery(document).ready(function(){
   jQuery('.telephone-number-footer, h4').click(function() {
      console.log("Telephone Number Clicked (Footer)");
      return ga('send', 'event', 'Telephone', 'click', 'footer');
   });
   jQuery('.telephone-number-body').click(function() {
     console.log("Telephone Number Clicked (Body)");
     return ga('send', 'event', 'Telephone', 'click', 'body');
   });
   jQuery('.telephone-number-menu').click(function() {
     console.log("Telephone Number Clicked (Menu)");
     return ga('send', 'event', 'Telephone', 'click', 'menu');
   });
   jQuery('.telephone-number-header').click(function() {
     console.log("Telephone Number Clicked (Header)");
    return ga('send', 'event', 'Telephone', 'click', 'header');
   });
});
</script>

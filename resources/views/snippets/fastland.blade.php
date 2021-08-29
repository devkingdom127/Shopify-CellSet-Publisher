<div id="product-description-fastland-%%destination%%%"></div>

<script>
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      // Show html content
      var description_element = document.getElementById("product-description-fastland-%%destination%%%")
      var data = JSON.parse(this.responseText)
      description_element.innerHTML = data.html

      // Add styles
      data.styles.forEach(function(style) {
        link = document.createElement('LINK');
        link.rel = 'stylesheet';
        link.href = style;
        link.type = 'text/css';
        description_element.appendChild(link);
      });
    }
  };
  xhttp.open("GET", "%app_domain%/api/products/%%product.handle%%%?destination=%%destination%%%&shop=https://%%shop.permanent_domain%%%/", true);
  xhttp.send();
</script>

<style scoped>
  @font-face { font-family: 'HelveticaNeueLTStd_HvCn'; src: url(%% 'HelveticaNeueLTStd_HvCn.woff' | asset_url %%%) format("woff"); }
  @font-face { font-family: 'HelveticaNeueLTStd_Cn'; src: url(%% 'HelveticaNeueLTStd_Cn.woff' | asset_url %%%) format("woff"); }
  @font-face { font-family: 'HelveticaNeueLTStd_BdCn'; src: url(%% 'HelveticaNeueLTStd_BdCn.woff' | asset_url %%%) format("woff"); }
  @font-face { font-family: 'BasicBullets'; src: url(%% 'BasicBullets.woff' | asset_url %%%) format("woff"); }
  @font-face { font-family: 'MyriadPro-Regular'; src: url(%% 'MyriadPro-Regular.woff' | asset_url %%%) format("woff"); }
  @font-face { font-family: 'MyriadPro-Light'; src: url(%% 'MyriadPro-Light.woff' | asset_url %%%) format("woff"); }
  @font-face { font-family: 'MyriadPro-Bold'; src: url(%% 'MyriadPro-Bold.woff' | asset_url %%%) format("woff"); }
  @font-face { font-family: 'MyriadPro_Regular'; src: url(%% 'MyriadPro_Regular.woff' | asset_url %%%) format("woff"); }
  @font-face { font-family: 'MyriadPro_Light'; src: url(%% 'MyriadPro_Light.woff' | asset_url %%%) format("woff"); }
  @font-face { font-family: 'MyriadPro_Bold'; src:url(%% 'MyriadPro_Bold.woff' | asset_url %%%) format("woff"); }
  @font-face { font-family: 'MyriadPro_BoldCond'; src:url(%% 'MyriadPro_BoldCond.woff' | asset_url %%%) format("woff"); }
  
  #product-description-fastland * { -webkit-font-smoothing: auto !important; }
</style>
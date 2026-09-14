(function(){
  'use strict';
  if(typeof window.addToCart!=='function') return;

  var originalAddToCart=window.addToCart;
  window.addToCart=function(p,c,size){
    originalAddToCart(p,c,size);
    var rawId=String((p&&p.id)!=null?p.id:'');
    var productId=parseInt(rawId.split('-')[0],10)||0;
    var key=rawId+'|'+size;
    if(!Array.isArray(window.CART)) return;
    for(var i=0;i<window.CART.length;i++){
      if(window.CART[i].key===key){
        window.CART[i].productId=productId;
        break;
      }
    }
  };
})();
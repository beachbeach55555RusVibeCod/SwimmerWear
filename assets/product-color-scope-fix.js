(function(){
  'use strict';
  if(!Array.isArray(window.PRODUCTS)) return;

  window.productByColor=function(name,colorId){
    var currentId=window.sel&&window.sel.productId!=null?String(window.sel.productId):'';
    var base=currentId.split('-')[0];
    for(var i=0;i<window.PRODUCTS.length;i++){
      var p=window.PRODUCTS[i];
      if(String(p.id).split('-')[0]===base && p.colorId===colorId) return p;
    }
    return null;
  };
})();

/* Spark English Center — interior pages: tabs (works alongside spark.js + blog.js) */
(function(){
  'use strict';

  // Article reading progress (html/blog-post.html .read-progress)
  var bar = document.querySelector('.read-progress');
  if (bar) {
    var onScroll = function () {
      var doc = document.documentElement;
      var max = doc.scrollHeight - doc.clientHeight;
      var pct = max > 0 ? (doc.scrollTop / max) * 100 : 0;
      bar.style.width = Math.min(100, Math.max(0, pct)) + '%';
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  document.querySelectorAll('.tabs').forEach(function(tabs){
    var buttons=Array.prototype.slice.call(tabs.querySelectorAll('.tab-list button'));
    var wrap=tabs.querySelector('.tab-panels')||tabs;
    var panels=Array.prototype.slice.call(wrap.querySelectorAll('.tab-panel'));
    buttons.forEach(function(btn,i){
      btn.addEventListener('click',function(){
        buttons.forEach(function(b){b.classList.remove('active');});
        panels.forEach(function(p){p.classList.remove('active');});
        btn.classList.add('active');
        var key=btn.getAttribute('data-tab');
        var target=key?wrap.querySelector('[data-tab-panel="'+key+'"]'):panels[i];
        if(target)target.classList.add('active');
      });
    });
  });
})();

/* Blog listing: search + topic filter + pagination. Expects #postGrid .post-card[data-cat][data-title], #blogFilters .chip[data-filter], #blogSearch, #blogCount, #noResults, #pagination */
(function(){
var grid=document.getElementById('postGrid');if(!grid)return;
var PAGE_SIZE=parseInt(grid.getAttribute('data-page-size')||'9',10);
var cards=Array.prototype.slice.call(grid.querySelectorAll('.post-card'));
var chips=Array.prototype.slice.call(document.querySelectorAll('#blogFilters .chip[data-filter]')).filter(function(ch){return ch.tagName==='BUTTON'});
var search=document.getElementById('blogSearch'),count=document.getElementById('blogCount'),noResults=document.getElementById('noResults'),pagination=document.getElementById('pagination');
if(search&&search.form){search=null}
var params=new URLSearchParams(location.search);
var pre=params.get('category_name')||params.get('category')||'';
var activeFilter=pre||'all',page=1;
if(pre){chips.forEach(function(c){c.classList.toggle('active',c.dataset.filter===pre)})}
function matched(){var q=search?search.value.trim().toLowerCase():'';return cards.filter(function(c){var cats=(c.dataset.cat||'').split(' ');var f=activeFilter==='all'||cats.indexOf(activeFilter)!==-1;var s=!q||(c.dataset.title||'').toLowerCase().indexOf(q)!==-1||c.textContent.toLowerCase().indexOf(q)!==-1;return f&&s;})}
function btn(cls,html,dis,fn){var b=document.createElement('button');b.className=cls;b.innerHTML=html;b.disabled=!!dis;b.addEventListener('click',fn);return b}
function renderPagination(total){if(!pagination)return;var pages=Math.ceil(total/PAGE_SIZE);pagination.innerHTML='';pagination.classList.toggle('hide',pages<=1);if(pages<=1)return;
pagination.appendChild(btn('page-btn nav','<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>Prev',page===1,function(){page--;apply();scrollTop()}));
for(var i=1;i<=pages;i++)(function(n){pagination.appendChild(btn('page-btn'+(n===page?' active':''),String(n),false,function(){page=n;apply();scrollTop()}))})(i);
pagination.appendChild(btn('page-btn nav','Next<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>',page===pages,function(){page++;apply();scrollTop()}))}
function scrollTop(){var y=grid.getBoundingClientRect().top+window.pageYOffset-120;window.scrollTo({top:y,behavior:'smooth'})}
function apply(){var r=matched();var pages=Math.max(1,Math.ceil(r.length/PAGE_SIZE));if(page>pages)page=pages;var set=r.slice((page-1)*PAGE_SIZE,page*PAGE_SIZE);cards.forEach(function(c){c.classList.toggle('show',set.indexOf(c)!==-1)});
if(count)count.textContent=r.length+(r.length===1?' article':' articles')+(activeFilter!=='all'?' in this topic':'');if(noResults)noResults.classList.toggle('show',r.length===0);renderPagination(r.length)}
chips.forEach(function(ch){ch.addEventListener('click',function(){chips.forEach(function(c){c.classList.remove('active')});ch.classList.add('active');activeFilter=ch.dataset.filter;page=1;apply()})});
if(search)search.addEventListener('input',function(){page=1;apply()});
apply();
})();

(function(){
'use strict';
const $=id=>document.getElementById(id);
const tabs=document.querySelectorAll('.zyvora-tabs button');
tabs.forEach(b=>b.addEventListener('click',()=>{tabs.forEach(x=>x.classList.toggle('active',x===b));document.querySelectorAll('.zyvora-tab-panel').forEach(p=>p.classList.toggle('active',p.dataset.panel===b.dataset.tab));}));
async function post(action,fields){
 const data=new FormData();data.append('action',action);data.append('nonce',ZyvoraAI.nonce);
 Object.keys(fields).forEach(k=>data.append(k,fields[k]));
 const res=await fetch(ZyvoraAI.ajaxUrl,{method:'POST',credentials:'same-origin',body:data});
 const json=await res.json();if(!res.ok||!json.success)throw new Error(json.data&&json.data.message?json.data.message:'Request failed');return json.data;
}
const generate=$('zyvora-generate');
if(generate)generate.addEventListener('click',async()=>{
 const topic=$('zyvora-topic').value.trim();if(!topic){$('zyvora-topic').focus();return;}
 generate.disabled=true;generate.textContent='Generating…';$('zyvora-studio-status').textContent='Contacting your AI provider…';
 try{const r=await post('zyvora_ai_generate',{topic,task:$('zyvora-task').value,language:$('zyvora-language').value});$('zyvora-output').value=r.content;$('zyvora-output-wrap').hidden=false;$('zyvora-studio-status').textContent='Generated. Review and edit before saving.';}
 catch(e){$('zyvora-studio-status').textContent='Error: '+e.message;}
 finally{generate.disabled=false;generate.textContent='Generate content ↗';}
});
if($('zyvora-copy'))$('zyvora-copy').addEventListener('click',async()=>{try{await navigator.clipboard.writeText($('zyvora-output').value);$('zyvora-studio-status').textContent='Copied to clipboard.';}catch(e){$('zyvora-studio-status').textContent='Clipboard unavailable. Select and copy the text manually.';}});
if($('zyvora-save-draft'))$('zyvora-save-draft').addEventListener('click',async()=>{
 const b=$('zyvora-save-draft');b.disabled=true;b.textContent='Saving…';
 try{const r=await post('zyvora_ai_save_draft',{content:$('zyvora-output').value});$('zyvora-studio-status').textContent=r.message+' Open draft: '+r.edit_url;if(r.edit_url)window.open(r.edit_url,'_blank','noopener');}
 catch(e){$('zyvora-studio-status').textContent='Error: '+e.message;}
 finally{b.disabled=false;b.textContent='Save as WordPress draft';}
});
const form=$('zyvora-chat-form');
if(form){const prompt=$('zyvora-prompt'),log=$('zyvora-chat-log'),button=form.querySelector('button[type=submit]');
 function message(role,text){const item=document.createElement('div');item.className='zyvora-message '+(role==='user'?'zyvora-user':'zyvora-assistant');const h=document.createElement('strong');h.textContent=role==='user'?'YOU':'ZYVORA AI';const p=document.createElement('p');p.textContent=text;item.append(h,p);log.append(item);log.scrollTop=log.scrollHeight;return p;}
 form.addEventListener('submit',async e=>{e.preventDefault();const text=prompt.value.trim();if(!text||button.disabled)return;message('user',text);prompt.value='';button.disabled=true;button.textContent='Thinking…';const pending=message('assistant','Connecting to your AI provider…');try{const r=await post('zyvora_ai_chat',{prompt:text});pending.textContent=r.answer;}catch(err){pending.textContent='Error: '+err.message;}finally{button.disabled=false;button.textContent='Send message ↗';log.scrollTop=log.scrollHeight;prompt.focus();}});
}
})();
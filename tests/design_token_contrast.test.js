// Run with Node.js: node tests/design_token_contrast.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const css = fs.readFileSync('public/assets/css/app.css','utf8');
function tokens(block) {
  return Object.fromEntries([...block.matchAll(/--([\w-]+):\s*([^;]+);/g)].map(m=>[m[1],m[2].trim()]));
}
const light = tokens(css.match(/:root\s*\{([^}]+)\}/)[1]);
const dark = {...light,...tokens(css.match(/body\[data-theme='dark'\]\s*\{([^}]+)\}/)[1])};
function luminance(hex) {
  const rgb = hex.slice(1).match(/../g).map(v=>parseInt(v,16)/255).map(v=>v<=0.04045?v/12.92:((v+0.055)/1.055)**2.4);
  return 0.2126*rgb[0]+0.7152*rgb[1]+0.0722*rgb[2];
}
for (const [theme,palette] of Object.entries({light,dark})) {
  function color(name) {
    const value=palette[name];
    const alias=value.match(/^var\(--([\w-]+)\)$/);
    if(alias)return color(alias[1]);
    assert.match(value,/^#[\da-f]{6}$/i,`${name}: expected opaque token`);
    return value;
  }
  function contrast(a,b,min) {
    const values=[luminance(color(a)),luminance(color(b))].sort((x,y)=>y-x);
    const ratio=(values[0]+0.05)/(values[1]+0.05);
    assert.ok(ratio>=min,`${theme} ${a}/${b}: ${ratio.toFixed(2)} < ${min}`);
  }
  contrast('color-text-muted','color-surface',4.5);
  contrast('color-primary-text','color-surface',4.5);
  contrast('color-danger-text','color-danger-bg',4.5);
  contrast('color-success-text','color-success-bg',4.5);
  contrast('color-focus','color-surface',3);
  contrast('color-primary-contrast','color-primary',4.5);
}
console.log('PASS shared light/dark text and focus token contrast');

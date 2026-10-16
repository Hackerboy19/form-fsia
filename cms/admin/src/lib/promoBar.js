// The website's own /cms/promo.js, run in preview mode, so admin previews are
// built by exactly the code visitors get.
import promoSource from '../../../promo.js?raw';

let lib = null;
export function promoLib() {
  if (!lib) {
    window.__fsiaPromoPreview = true;
    new Function(promoSource)(); // eslint-disable-line no-new-func
    lib = window.FSIAPromo;
  }
  return lib;
}

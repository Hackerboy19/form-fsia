<?php
/* This file is an include, not a page of its own. Opened directly it has no $connect
   and used to emit a broken half-section, so send visitors to the page that actually
   shows this section. 301 so search engines drop this URL and keep contact.php.
   Every page that includes it (contact.php, our-teams.php,
   verified_user_details_*.php) loads config.php first, so $connect is always set in
   the normal path and this block does nothing there. */
if (!isset($connect) || !$connect) {
	if (!headers_sent()) {
		header('Location: https://www.fsia.in/contact.php', true, 301);
	}
	exit;
}
?>
<style>
/* This partial is included by pages that load Tailwind (contact.php) and by pages
   that do not (our-teams.php, verified_user_details_*.php). The scoped rules below
   carry the whole design on their own, so the section looks identical either way.
   They are written as `.fsia-smp .x` to stay deterministic when Tailwind's
   single-class utilities are present too. */
.fsia-smp { padding: 48px 0; }
.fsia-smp *, .fsia-smp *::before, .fsia-smp *::after { box-sizing: border-box; }

/* ---- heading ---- */
.fsia-smp .fsia-smp-head { text-align: center; max-width: 48rem; margin: 0 auto 3rem; padding: 2rem 1rem 0; }
.fsia-smp .fsia-smp-badge { display: inline-flex; align-items: center; gap: .5rem; background: #f1f5f9;
  border: 1px solid #e2e8f0; border-radius: 9999px; padding: .375rem 1rem; margin-bottom: .75rem; }
.fsia-smp .fsia-smp-dot { width: 6px; height: 6px; border-radius: 9999px; background: #f59e0b; display: inline-block; }
.fsia-smp .fsia-smp-badge span:last-child { font-size: 11px; font-weight: 700; color: #475569;
  text-transform: uppercase; letter-spacing: .3em; }
.fsia-smp .fsia-smp-title { font-family: 'Playfair Display', Georgia, serif; font-weight: 900;
  font-size: 1.875rem; line-height: 1.2; color: #0f172a; letter-spacing: -.025em; text-transform: uppercase; margin: 0; }
@media (min-width: 768px) { .fsia-smp .fsia-smp-title { font-size: 2.25rem; } }
.fsia-smp .fsia-smp-rule { width: 4rem; height: 4px; margin: 1rem auto 0; border-radius: 9999px;
  background: linear-gradient(to right, transparent, #f59e0b, transparent); }

/* ---- grid ---- */
.fsia-smp .fsia-smp-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem;
  max-width: 80rem; margin: 0 auto; padding: 0 1rem; }
@media (min-width: 640px)  { .fsia-smp .fsia-smp-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (min-width: 768px)  { .fsia-smp .fsia-smp-grid { gap: 2rem; } }
@media (min-width: 1024px) { .fsia-smp .fsia-smp-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }

/* ---- magazine card ---- */
.fsia-smp .fsia-smp-card { position: relative; width: 100%; aspect-ratio: 4 / 5; border-radius: 2rem;
  overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -4px rgba(0,0,0,.1);
  transition: box-shadow .5s, transform .5s; cursor: pointer; background: #0f172a; }
.fsia-smp .fsia-smp-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px -15px rgba(212,175,55,.3); }

.fsia-smp .fsia-smp-imglink { position: absolute; inset: 0; display: block; z-index: 1; }
.fsia-smp .fsia-smp-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;
  transition: transform .7s cubic-bezier(0,0,.2,1); }
.fsia-smp .fsia-smp-card:hover .fsia-smp-img { transform: scale(1.1); }

.fsia-smp .fsia-smp-veil { position: absolute; inset: 0; z-index: 2; pointer-events: none; opacity: .8;
  transition: opacity .5s;
  background: linear-gradient(to top, #020617 0%, rgba(15,23,42,.4) 50%, rgba(15,23,42,0) 100%); }
.fsia-smp .fsia-smp-card:hover .fsia-smp-veil { opacity: .9; }

/* The badges and the caption block sit above the image link, so without this the
   top strip and the whole bottom half of the card swallowed the click and the
   card looked dead. They carry no links of their own, so they let clicks pass
   through; the name anchor inside the caption takes its own back. */
.fsia-smp .fsia-smp-badges { position: absolute; top: 1.25rem; left: 1.25rem; right: 1.25rem; z-index: 10;
  display: flex; justify-content: space-between; align-items: flex-start; gap: .5rem;
  pointer-events: none; }
/* Category names run long ("The Real Super Heroes", "Forever International Award"),
   so the badge wraps to a second line instead of cutting the words off. */
.fsia-smp .fsia-smp-cat { background: rgba(0,0,0,.4); -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px);
  color: #fbbf24; font-size: 9px; font-weight: 700; padding: .375rem .75rem; border-radius: 1rem;
  text-transform: uppercase; letter-spacing: .12em; border: 1px solid rgba(245,158,11,.3);
  max-width: calc(100% - 4.5rem); white-space: normal; overflow-wrap: break-word; line-height: 1.35;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.fsia-smp .fsia-smp-year { background: rgba(255,255,255,.1); -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px);
  color: #fff; font-size: 10px; font-weight: 700; padding: .375rem .75rem; border-radius: 9999px;
  border: 1px solid rgba(255,255,255,.2); white-space: nowrap; flex: 0 0 auto; align-self: flex-start; }

.fsia-smp .fsia-smp-bottom { position: absolute; left: 0; right: 0; bottom: 0; height: 50%; padding: 1.5rem;
  z-index: 10; display: flex; flex-direction: column; justify-content: flex-end;
  pointer-events: none; }
.fsia-smp .fsia-smp-bottom a { pointer-events: auto; }
.fsia-smp .fsia-smp-name { font-family: 'Playfair Display', Georgia, serif; font-size: 1.5rem; font-weight: 700;
  color: #fff; margin: 0 0 .25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  text-shadow: 0 4px 6px rgba(0,0,0,.4); }
@media (min-width: 768px) { .fsia-smp .fsia-smp-name { font-size: 1.875rem; } }
.fsia-smp .fsia-smp-name a { color: inherit; text-decoration: none; }

/* ---- View More Profiles ---- */
.fsia-smp-more { text-align: center; margin-top: 3rem; padding: 0 1rem; }
.fsia-smp-more a { display: inline-flex; align-items: center; justify-content: center; gap: .625rem;
  background: linear-gradient(to right, #f59e0b, #d97706); color: #020617 !important; font-weight: 900;
  font-size: .8rem; text-transform: uppercase; letter-spacing: .14em; padding: 1.05rem 2.5rem;
  border-radius: 9999px; text-decoration: none; border: 1px solid rgba(212,175,55,.5);
  box-shadow: 0 10px 25px -10px rgba(245,158,11,.55); transition: box-shadow .3s, transform .3s, background .3s; }
.fsia-smp-more a:hover { transform: translateY(-2px); box-shadow: 0 16px 34px -10px rgba(245,158,11,.7); }
.fsia-smp-more a svg { width: 1rem; height: 1rem; transition: transform .3s; }
.fsia-smp-more a:hover svg { transform: translateX(4px); }
.fsia-smp-more p { margin: .9rem 0 0; font-size: .78rem; color: #64748b; }

/* entrance animation, scoped so the partial does not depend on the host page */
.fsia-smp .fade-in-up { animation: fsiaSmpFadeInUp .8s cubic-bezier(.16,1,.3,1) forwards; opacity: 0; transform: translateY(30px); }
@keyframes fsiaSmpFadeInUp { to { opacity: 1; transform: translateY(0); } }
@media (prefers-reduced-motion: reduce) {
  .fsia-smp .fade-in-up { animation: none; opacity: 1; transform: none; }
}
</style>

<div class="verifiedmem community_members socialmediaprofile fsia-smp py-3">

    <div class="fsia-smp-head text-center max-w-3xl mx-auto mb-12 pt-8 px-4">
        <div class="fsia-smp-badge inline-flex items-center gap-2 bg-slate-100 border border-slate-200 rounded-full px-4 py-1.5 mb-3">
            <span class="fsia-smp-dot w-1.5 h-1.5 rounded-full bg-amber-500"></span>
            <span class="text-[11px] font-bold text-slate-600 uppercase tracking-[0.3em]">Official Participants</span>
        </div>
        <h2 class="fsia-smp-title text-3xl md:text-4xl font-black text-slate-900 tracking-tight uppercase" style="font-family:'Playfair Display', serif;">Social Media Profiles</h2>
        <div class="fsia-smp-rule w-16 h-1 bg-gradient-to-r from-transparent via-amber-500 to-transparent mx-auto rounded-full mt-4"></div>
    </div>

    <div class="fsia-smp-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8 max-w-7xl mx-auto px-4">
<?php
	/* Profiles edited on the Star India sub-site live on starindia.fsia.in, the rest
	   on www.fsia.in. registration.starindia_action says which: 'none' = www,
	   anything else = starindia. Same rule as fsia_gallery_profile_url() in
	   gallery_data.php. Without this the sub-site profiles 404. */
	if (!function_exists('fsia_smp_profile_url')) {
		function fsia_smp_profile_url($rawUrl, $starIndiaAction, $fallbackPath) {
			$slug = ltrim(trim((string) $rawUrl), '/');
			if ($slug === '') {
				// the verified_user_details_*.php fallbacks only exist on www
				return 'https://www.fsia.in/' . ltrim((string) $fallbackPath, '/');
			}
			if (strpos($slug, 'http') === 0) { return $slug; }
			$action = strtolower(trim((string) $starIndiaAction));
			$host = ($action !== '' && $action !== 'none')
				? 'https://starindia.fsia.in/'
				: 'https://www.fsia.in/';
			return $host . $slug;
		}
	}

	/* Newer records store the photo as a full URL (some on starindia.fsia.in), while
	   older ones store a path relative to the main site. Prefixing every value with
	   https://fsia.in/ produced links like
	   https://fsia.in/https://starindia.fsia.in/uploads/... which 404. */
	if (!function_exists('fsia_smp_image_url')) {
		function fsia_smp_image_url($path) {
			$p = trim((string) $path);
			if ($p === '') { return ''; }
			if (strpos($p, 'http') === 0) { return $p; }
			return 'https://www.fsia.in/' . ltrim($p, '/');
		}
	}

	/* Two queries instead of one.
	   The old single query ended with `completion >= 75`, and pageant registrations
	   rarely reach that: their median completion is 45, so only 26 of the 920 rows
	   that passed were Miss/Mrs/Teen India. With `order by rand()` the grid was
	   awardees almost every time.
	   Now the gate is what the card actually needs -- a name and a usable photo --
	   and the two groups are fetched separately so pageants are always represented.
	   Both are ordered newest-first. */
	$smp_base = "SELECT year(date_of_reg) as jyear,pdate as date1,year as year1,setdefault,url,starindia_action,regtype,id as userid,first_name,last_name,image,facebook,instagram,twitter,youtube,awardee,nationalwinner,status,statewinner,citywinner,ptitle1,ptitle2,ptitle from registration
		where (status=1 or status=3) and verification=1 and suspended=0
		and trim(first_name) <> ''
		and (setdefault <> '' or trim(both ',' from image) <> '')";

	/* Each group pulls a pool of the 40 newest and then shuffles it, so the section
	   stays on recent profiles but shows a different set on every page load. */
	$smp_pool = 40; $smp_show = 10;
	$smp_pageant = array(); $smp_other = array();
	$q_pag = mysqli_query($connect, $smp_base." and regtype in (6,7,13) order by date_of_reg desc limit 0,".$smp_pool);
	$q_oth = mysqli_query($connect, $smp_base." and regtype not in (6,7,13) order by date_of_reg desc limit 0,".$smp_pool);
	if($q_pag){ while($r = mysqli_fetch_assoc($q_pag)){ $smp_pageant[] = $r; } }
	if($q_oth){ while($r = mysqli_fetch_assoc($q_oth)){ $smp_other[] = $r; } }

	shuffle($smp_pageant);
	shuffle($smp_other);
	$smp_pageant = array_slice($smp_pageant, 0, $smp_show);
	$smp_other   = array_slice($smp_other,   0, $smp_show);

	// interleave the two lists so both kinds show across the whole grid
	$smp_rows = array();
	$smp_max = max(count($smp_pageant), count($smp_other));
	for($smp_i = 0; $smp_i < $smp_max; $smp_i++){
		if(isset($smp_pageant[$smp_i])) $smp_rows[] = $smp_pageant[$smp_i];
		if(isset($smp_other[$smp_i]))   $smp_rows[] = $smp_other[$smp_i];
	}

	$tt = 0;
	foreach($smp_rows as $bressp){
	$qrys2=mysqli_fetch_array(mysqli_query($connect,"SELECT count(*) as clike from tbl_like where userid=$bressp[userid] and postid=''"));
	$qrys22=mysqli_fetch_array(mysqli_query($connect,"SELECT count(*) as mlike from tbl_like where userid=$bressp[userid] and postid='' and cid='$_SESSION[userid]'"));
	$qrys3=mysqli_fetch_array(mysqli_query($connect,"SELECT count(*) as ccomment from tbl_comment where user_id=$bressp[userid] and postid=''"));

	if($bressp['url']<>""){
			$url = $bressp['url'];
		}else{

				if($bressp['regtype']==6){
					$url = "verified_user_details_missindia.php?id=".$bressp['userid'];
				}else if($bressp['regtype']==13){
					$url = "verified_user_details_missindiateen.php?id=".$bressp['userid'];
				}else if($bressp['regtype']==7){
					$url = "verified_user_details_mrsindia.php?id=".$bressp['userid'];
				}else if($bressp['regtype']==9 || $bressp['regtype']==2){
					$url = "verified_user_details_women.php?id=".$bressp['userid'];
				}else if($bressp['regtype']==8 || $bressp['regtype']==1 || $bressp['regtype']==0 || $bressp['regtype']==14){
					$url = "verified_user_details_new.php?id=".$bressp['userid'];
				}else if($bressp['regtype']==10){
					$url = "verified_user_details_fashion_designer.php?id=".$bressp['userid'];
				}else if($bressp['regtype']==11){
					$url = "verified_user_details_makeup_artist.php?id=".$bressp['userid'];
				}else if($bressp['regtype']==12){
					$url = "verified_user_details_pageant_trainer.php?id=".$bressp['userid'];
				}


		}

		$img_1 = ltrim($bressp['image'],",");

		if($bressp['setdefault']!=""){
					$filename=$bressp['setdefault'];
				} else {
					$filename="";
					$cust_imgsp=explode(",",$img_1);
					foreach($cust_imgsp as $cvaw){
						if($cvaw<>""){
							if($filename==""){
								$filename=$cvaw;
							}
						}
					}
				}



		$ressp[]=$bressp;
		$ressp[$tt]['image']= ltrim($bressp['image'],",");
		$ressp[$tt]['like']= $qrys2['clike'];
		$ressp[$tt]['mlike']=$qrys22['mlike'];
		$ressp[$tt]['comment']= $qrys3['ccomment'];
		$ressp[$tt]['url']=$url;
		$ressp[$tt]['profile']=$filename;
		$ressp[$tt]['profile_url']=fsia_smp_profile_url($bressp['url'], isset($bressp['starindia_action']) ? $bressp['starindia_action'] : '', $url);
?>
        <div class="fsia-smp-card relative group w-full aspect-[4/5] rounded-[2rem] overflow-hidden shadow-lg hover:shadow-[0_20px_40px_-15px_rgba(212,175,55,0.3)] transition-all duration-500 transform hover:-translate-y-2 cursor-pointer fade-in-up">

            <a class="fsia-smp-imglink vdetails" href="<?php print $ressp[$tt]['profile_url'] ?>" rel="nofollow" aria-label="<?php print $ressp[$tt]['first_name']; ?>"><img src="<?php print fsia_smp_image_url($ressp[$tt]['profile']); ?>" alt="<?php print $ressp[$tt]['first_name']; ?>" title="<?php print $ressp[$tt]['first_name']; ?>" loading="lazy" class="fsia-smp-img absolute inset-0 w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-110"></a>

            <div class="fsia-smp-veil absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/40 to-transparent opacity-80 group-hover:opacity-90 transition-opacity duration-500"></div>

            <div class="fsia-smp-badges absolute top-5 left-5 right-5 flex justify-between items-start z-10">
                <span class="fsia-smp-cat bg-black/40 backdrop-blur-md text-amber-400 text-[9px] font-bold px-3 py-1.5 rounded-2xl uppercase tracking-[0.12em] border border-amber-500/30"><?php
if($ressp[$tt]['regtype']==0){
		print $Title = "Guest";
	}else if($ressp[$tt]['regtype']==14){
		print $Title = "Forever International Award";
	}else if($ressp[$tt]['regtype']==6){
		print $Title = "Forever Miss India";
	}else if($ressp[$tt]['regtype']==13){
		print $Title = "Forever Miss Teen India";
	}else if($ressp[$tt]['regtype']==7){
		print $Title = "Forever Mrs India";
	}else if($ressp[$tt]['regtype']==9 || $ressp[$tt]['regtype']==2){
		print $Title = "The Real Super Women";
	}else if($ressp[$tt]['regtype']==8 || $ressp[$tt]['regtype']==1){
		print $Title = "The Real Super Heroes";
	}else if($ressp[$tt]['regtype']==10){
		print $Title = "Forever Fashion Designer";
	}else if($ressp[$tt]['regtype']==11){
		print $Title = "Forever Makeup Artist";
	}else if($ressp[$tt]['regtype']==12){
		print $Title = "Forever Pageant Trainer";
	}else if($ressp[$tt]['regtype']==26){
		print $Title = "Forever Business Award";
	}else if($ressp[$tt]['regtype']==32 || $ressp[$tt]['regtype']==40){
		print $Title = "The Real Super Women";
	}else{
		// 33, 34, 35 and anything added later have no label of their own yet,
		// so the badge falls back to the brand instead of rendering empty
		print $Title = "Forever Star India";
	}
													?></span>
                <span class="fsia-smp-year bg-white/10 backdrop-blur-md text-white text-[10px] font-bold px-3 py-1.5 rounded-full border border-white/20"><?php print $ressp[$tt]['jyear']; ?></span>
            </div>

            <div class="fsia-smp-bottom absolute bottom-0 left-0 right-0 p-6 z-10 flex flex-col justify-end h-1/2">
                <h3 class="fsia-smp-name text-2xl md:text-3xl font-bold text-white mb-1 truncate drop-shadow-md" style="font-family:'Playfair Display', serif;"><a class="vdetails" href="<?php print $ressp[$tt]['profile_url'] ?>" rel="nofollow"><?php print $ressp[$tt]['first_name']; ?></a></h3>
            </div>
        </div>

<?php $tt++; }?>
    </div>

    <div class="fsia-smp-more text-center mt-12 px-4">
        <a href="https://starindia.fsia.in/" target="_blank" rel="noopener">
            <span>View More Profiles</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </a>
        <p>Browse the full Star India directory</p>
    </div>
</div>

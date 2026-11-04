<?php
/**
 * Title: Tab panel (image + copy)
 * Slug: sparkvn/tab-panel-split
 * Categories: sparkvn-layout
 * Description: One program-level tab: photo left, title / skills / outcome right. Use + Add tab on Spark Tabs, or insert this inside Tabs.
 * Viewport Width: 1180
 */
?>
<!-- wp:sparkvn/tab-panel {"label":"Level title","layout":"split"} -->
<div class="wp-block-sparkvn-tab-panel tab-panel split">
<!-- wp:group {"className":"split-media"} -->
<div class="wp-block-group split-media">
<!-- wp:image {"url":"assets/img/campus-classroom.jpg","alt":"Program level"} -->
<figure class="wp-block-image"><img src="assets/img/campus-classroom.jpg" alt="Program level"/></figure>
<!-- /wp:image -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"split-copy copy"} -->
<div class="wp-block-group split-copy copy">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Level title</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>What students work on at this level.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading">Skills Developed</h4>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>Skill one</li>
<!-- /wp:list-item --><!-- wp:list-item -->
<li>Skill two</li>
<!-- /wp:list-item --><!-- wp:list-item -->
<li>Skill three</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading">Outcome</h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>What students can do after this level.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:sparkvn/tab-panel -->

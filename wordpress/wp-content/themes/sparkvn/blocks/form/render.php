<?php
/**
 * Spark form card.
 *
 * @var array $attributes Attributes.
 * @package Spark_VN
 */

$variation = $attributes['variation'] ?? 'contact';
$heading   = $attributes['heading'] ?? '';
$sub       = $attributes['sub'] ?? '';

$submit     = __( 'Send', 'sparkvn' );
$note       = '';
$success_h  = __( 'Thank you!', 'sparkvn' );
$success_p  = __( 'Your message has been received. We will be in touch shortly.', 'sparkvn' );

$success_extra = array();

if ( 'assessment' === $variation ) {
	$submit    = __( 'Book My Free Assessment', 'sparkvn' );
	$note      = __( '30–45 min · Thao Dien · We reply within 1 business day', 'sparkvn' );
	$success_h = __( 'Thank you for contacting us.', 'sparkvn' );
	$success_p = __( 'We will get back to you as soon as possible', 'sparkvn' );
} elseif ( 'contact' === $variation ) {
	$submit    = __( 'Send Enquiry', 'sparkvn' );
	$heading   = '';
	$sub       = '';
	$success_h = __( 'Thank you for contacting us.', 'sparkvn' );
	$success_p = __( 'We will get back to you as soon as possible.', 'sparkvn' );
} elseif ( 'resource' === $variation ) {
	$submit    = __( 'Get Resources', 'sparkvn' );
	$note      = __( 'No spam — unsubscribe anytime.', 'sparkvn' );
	$success_h = __( 'Thank you for signing up!', 'sparkvn' );
	$success_p = __( 'Keep an eye on your inbox for access to our free resources, literacy tips, and future learning materials from Spark.', 'sparkvn' );
} elseif ( 'ask-teacher' === $variation ) {
	$submit    = __( 'Send Question', 'sparkvn' );
	$success_h = __( 'Thank you for your question!', 'sparkvn' );
	$success_p = __( 'Your question has been submitted to the Spark teaching team for review. Selected questions may be answered in a future Ask a Teacher article so that other parents can benefit from the guidance as well.', 'sparkvn' );
	$success_extra = array(
		__( 'While we may not be able to publish every submission, we carefully review all questions and use them to help shape future content and resources for families.', 'sparkvn' ),
		__( 'Be sure to check back regularly for new articles and answers from our teaching team.', 'sparkvn' ),
	);
}

$langs = array( 'English', 'Vietnamese', 'Korean', 'Chinese', 'French', 'Spanish', 'Other' );
$avail = array(
	'wk' => __( 'Week nights', 'sparkvn' ),
	'we' => __( 'Weekends', 'sparkvn' ),
	'fl' => __( 'Flexible', 'sparkvn' ),
	'cu' => __( 'Custom', 'sparkvn' ),
);
$interests = array( 'Reading', 'Phonics', 'Writing', 'Vocabulary', 'IELTS', 'Academic English', 'All of the above' );
?>
<div class="form-card" id="formCard">
	<?php if ( $heading ) : ?><h3 class="fhead"><?php echo esc_html( $heading ); ?></h3><?php endif; ?>
	<?php if ( $sub ) : ?><p class="sub"><?php echo esc_html( $sub ); ?></p><?php endif; ?>
	<form id="bookForm" data-form="<?php echo esc_attr( $variation ); ?>" novalidate>
		<?php if ( 'assessment' === $variation ) : ?>
			<div class="fstep"><span class="fs-n">1</span><span class="fs-t"><?php esc_html_e( 'Contact details', 'sparkvn' ); ?></span></div>
			<div class="form-grid compact">
				<div class="field"><label for="fn"><?php esc_html_e( "Student's first name", 'sparkvn' ); ?> <span class="req">*</span></label><input id="fn" name="fn" type="text" required autocomplete="given-name" /><span class="err"><?php esc_html_e( 'Required.', 'sparkvn' ); ?></span></div>
				<div class="field"><label for="ln"><?php esc_html_e( "Student's last name", 'sparkvn' ); ?> <span class="req">*</span></label><input id="ln" name="ln" type="text" required autocomplete="family-name" /><span class="err"><?php esc_html_e( 'Required.', 'sparkvn' ); ?></span></div>
				<div class="field"><label for="phone"><?php esc_html_e( 'Phone / Zalo', 'sparkvn' ); ?> <span class="req">*</span></label><input id="phone" name="phone" type="tel" inputmode="tel" required autocomplete="tel" /><span class="err"><?php esc_html_e( 'Please enter a valid phone number.', 'sparkvn' ); ?></span></div>
				<div class="field"><label for="email"><?php esc_html_e( 'Email', 'sparkvn' ); ?> <span class="req">*</span></label><input id="email" name="email" type="email" required autocomplete="email" /><span class="err"><?php esc_html_e( 'Please enter a valid email.', 'sparkvn' ); ?></span></div>
			</div>
			<div class="fstep"><span class="fs-n">2</span><span class="fs-t"><?php esc_html_e( 'About your child', 'sparkvn' ); ?></span></div>
			<div class="form-grid compact cols-3">
				<div class="field"><label for="dob"><?php esc_html_e( 'Date of birth', 'sparkvn' ); ?></label><input id="dob" name="dob" type="date" /><span class="err"></span></div>
				<div class="field"><label for="grade"><?php esc_html_e( 'Grade level', 'sparkvn' ); ?></label><input id="grade" name="grade" type="text" placeholder="<?php echo esc_attr__( 'e.g. Year 4', 'sparkvn' ); ?>" /><span class="err"></span></div>
				<div class="field">
					<label for="lang"><?php esc_html_e( 'Primary language', 'sparkvn' ); ?></label>
					<select id="lang" name="lang">
						<?php foreach ( $langs as $lang ) : ?>
							<option><?php echo esc_html( $lang ); ?></option>
						<?php endforeach; ?>
					</select>
					<span class="err"></span>
				</div>
				<div class="field full"><label for="school"><?php esc_html_e( 'Current school', 'sparkvn' ); ?></label><input id="school" name="school" type="text" placeholder="<?php echo esc_attr__( 'e.g. BIS, ISHCMC, AIS…', 'sparkvn' ); ?>" /><span class="err"></span></div>
				<div class="field full">
					<span class="flabel"><?php esc_html_e( 'Availability', 'sparkvn' ); ?></span>
					<div class="chip-group" role="group" aria-label="<?php esc_attr_e( 'Student availability', 'sparkvn' ); ?>">
						<?php foreach ( $avail as $value => $label ) : ?>
							<label><input type="checkbox" name="av" value="<?php echo esc_attr( $value ); ?>" /><span><?php echo esc_html( $label ); ?></span></label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<details class="fmore">
				<summary><?php esc_html_e( 'Add teacher feedback or notes about your child', 'sparkvn' ); ?> <span class="fopt"><?php esc_html_e( '(optional)', 'sparkvn' ); ?></span></summary>
				<div class="form-grid compact">
					<div class="field full"><label for="fb"><?php esc_html_e( 'Teacher feedback from school', 'sparkvn' ); ?></label><textarea id="fb" name="fb" rows="2"></textarea><span class="err"></span></div>
					<div class="field full"><label for="more"><?php esc_html_e( 'Anything else we should know?', 'sparkvn' ); ?></label><textarea id="more" name="more" rows="2"></textarea><span class="err"></span></div>
				</div>
			</details>
		<?php elseif ( 'resource' === $variation ) : ?>
			<div class="form-grid compact">
				<div class="field"><label for="fn"><?php esc_html_e( 'First name', 'sparkvn' ); ?> <span class="req">*</span></label><input id="fn" name="fn" type="text" required autocomplete="given-name" /><span class="err"><?php esc_html_e( 'Required.', 'sparkvn' ); ?></span></div>
				<div class="field"><label for="ln"><?php esc_html_e( 'Last name', 'sparkvn' ); ?> <span class="req">*</span></label><input id="ln" name="ln" type="text" required autocomplete="family-name" /><span class="err"><?php esc_html_e( 'Required.', 'sparkvn' ); ?></span></div>
				<div class="field"><label for="email"><?php esc_html_e( 'Email', 'sparkvn' ); ?> <span class="req">*</span></label><input id="email" name="email" type="email" required autocomplete="email" /><span class="err"><?php esc_html_e( 'Please enter a valid email.', 'sparkvn' ); ?></span></div>
				<div class="field"><label for="phone"><?php esc_html_e( 'Phone / Zalo', 'sparkvn' ); ?></label><input id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" /><span class="err"></span></div>
				<div class="field full">
					<span class="flabel"><?php esc_html_e( 'What content are you most interested in?', 'sparkvn' ); ?></span>
					<div class="chip-group" role="group" aria-label="<?php esc_attr_e( 'Interests', 'sparkvn' ); ?>">
						<?php foreach ( $interests as $interest ) : ?>
							<label><input type="checkbox" name="int" value="<?php echo esc_attr( $interest ); ?>" /><span><?php echo esc_html( $interest ); ?></span></label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		<?php elseif ( 'ask-teacher' === $variation ) : ?>
			<div class="form-grid">
				<div class="field full"><label for="name"><?php esc_html_e( 'Name', 'sparkvn' ); ?> <span class="req">*</span></label><input id="name" name="name" type="text" required /><span class="err"><?php esc_html_e( 'Please enter your name.', 'sparkvn' ); ?></span></div>
				<div class="field full"><label for="email"><?php esc_html_e( 'Email', 'sparkvn' ); ?> <span class="req">*</span></label><input id="email" name="email" type="email" required /><span class="err"><?php esc_html_e( 'Please enter a valid email.', 'sparkvn' ); ?></span></div>
				<div class="field full"><label for="msg"><?php esc_html_e( 'Message', 'sparkvn' ); ?> <span class="req">*</span></label><textarea id="msg" name="msg" required></textarea><span class="err"><?php esc_html_e( 'Please enter your question.', 'sparkvn' ); ?></span></div>
			</div>
		<?php else : ?>
			<div class="form-grid">
				<div class="field full"><label for="name"><?php esc_html_e( 'Name:', 'sparkvn' ); ?> <span class="req">*</span></label><input id="name" name="name" type="text" required /><span class="err"><?php esc_html_e( 'Please enter your name.', 'sparkvn' ); ?></span></div>
				<div class="field"><label for="phone"><?php esc_html_e( 'Phone number', 'sparkvn' ); ?> <span class="req">*</span></label><input id="phone" name="phone" type="tel" inputmode="tel" required /><span class="err"><?php esc_html_e( 'Please enter a valid phone number.', 'sparkvn' ); ?></span></div>
				<div class="field"><label for="age"><?php esc_html_e( 'Age of Child', 'sparkvn' ); ?></label><input id="age" name="age" type="text" /><span class="err"></span></div>
				<div class="field"><label for="lang"><?php esc_html_e( 'Primary Language', 'sparkvn' ); ?></label><input id="lang" name="lang" type="text" /><span class="err"></span></div>
				<div class="field"><label for="interest"><?php esc_html_e( 'Tutoring or Educational Interest', 'sparkvn' ); ?></label><input id="interest" name="interest" type="text" /><span class="err"></span></div>
				<div class="field full"><label for="addr"><?php esc_html_e( 'Address:', 'sparkvn' ); ?></label><input id="addr" name="addr" type="text" /><span class="err"></span></div>
				<div class="field full"><label for="msg"><?php esc_html_e( 'Message:', 'sparkvn' ); ?> <span class="req">*</span></label><textarea id="msg" name="msg" required></textarea><span class="err"><?php esc_html_e( 'Please enter a message.', 'sparkvn' ); ?></span></div>
			</div>
		<?php endif; ?>
		<button type="submit" class="btn btn-primary btn-lg"><?php echo esc_html( $submit ); ?></button>
		<?php if ( $note ) : ?><p class="form-note"><?php echo esc_html( $note ); ?></p><?php endif; ?>
	</form>
	<div class="form-success" id="formSuccess">
		<div class="badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>
		<h3><?php echo esc_html( $success_h ); ?></h3>
		<p><?php echo esc_html( $success_p ); ?></p>
		<?php foreach ( $success_extra as $extra ) : ?>
			<p><?php echo esc_html( $extra ); ?></p>
		<?php endforeach; ?>
	</div>
</div>

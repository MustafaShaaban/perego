/**
 * Live-canvas markup for perego-theme/journal-comments (spec 021 C12; DECISIONS 2026-07-22).
 *
 * Renders the REAL comments section — the same tag + class skeleton `JournalCommentsRenderer` emits:
 * the `.comments` section, its count heading, the `.comment-list` (including the indented
 * `.comment--reply` variant), and the whole `#respond` form — so the editor canvas shows the actual
 * section instead of a bare sentence.
 *
 * **Locked** preview with no controls: every comment is real reader data, and the form's labels and
 * messages are translation-catalogue strings, not editorial copy. **The three comments shown are a
 * design preview**, not the post's own thread — resolving live comments in the editor would mean
 * duplicating the renderer's query and moderation rules, and the point of the canvas here is that an
 * editor can see where the section sits and how it is styled. Same precedent as the services-teaser
 * seed cards and the project-navigation related grid.
 */

/** Mirror of the renderer's English labels (`GlobalContent` + the comment form). */
export const SEED = {
	title: '3 Comments',
	formTitle: 'Leave a comment',
	replyingTo: 'Replying to',
	cancel: 'Cancel',
	name: 'Name',
	email: 'E-mail',
	emailNote: '(not published)',
	comment: 'Comment',
	reply: 'Reply',
	submit: 'Post comment',
	honeypot: 'Leave this field empty',
};

/** Three sample comments matching the fixture's shape: two top-level, one reply. */
export const SEED_COMMENTS = [
	{ id: 1, initials: 'SA', name: 'Sara Adel', date: 'July 9, 2026', text: 'This lines up exactly with what we saw after adding a loader animation.', isReply: false },
	{ id: 2, initials: 'ME', name: 'Mostafa Emam', date: 'July 9, 2026', text: 'Thanks Sara! The loader is almost always the highest-leverage first moment.', isReply: true },
	{ id: 3, initials: 'KH', name: 'Karim Hassan', date: 'July 8, 2026', text: 'That is such a useful filter. Stealing it for our next review.', isReply: false },
];

function CommentItem( { comment } ) {
	return (
		<li id={ `comment-${ comment.id }` } className={ comment.isReply ? 'comment comment--reply' : 'comment' }>
			<span className="comment__avatar" aria-hidden="true">{ comment.initials }</span>
			<div className="comment__body">
				<div className="comment__head">
					<span className="comment__name">{ comment.name }</span>
					<span className="comment__date">{ comment.date }</span>
				</div>
				<p className="comment__text">{ comment.text }</p>
				<a className="comment__reply" href="#respond">{ SEED.reply }</a>
			</div>
		</li>
	);
}

function CommentForm() {
	return (
		<div id="respond" className="comment-respond">
			<form className="comment-form page-form" id="commentform" method="post" action="#" noValidate>
				<h3 className="comment-form__title">{ SEED.formTitle }</h3>
				<p className="comment-form__reply-chip" hidden>
					<span>{ SEED.replyingTo } <b className="comment-form__reply-name"></b></span>
					{ ' ' }
					<button type="button" className="comment-form__cancel">{ SEED.cancel }</button>
				</p>
				<div className="field-row">
					<div className="field">
						<label htmlFor="cm-author">{ SEED.name }</label>
						<input type="text" id="cm-author" name="author" autoComplete="name" placeholder="Your name" required />
					</div>
					<div className="field">
						<label htmlFor="cm-email">
							{ SEED.email } <span className="field__note">{ SEED.emailNote }</span>
						</label>
						<input type="email" id="cm-email" name="email" autoComplete="email" placeholder="you@email.com" required />
					</div>
				</div>
				<div className="field">
					<label htmlFor="cm-comment">{ SEED.comment }</label>
					<textarea id="cm-comment" name="comment" rows="4" placeholder="Share your thoughts…" required></textarea>
				</div>
				<div className="comment-form__hp" aria-hidden="true">
					<label>{ SEED.honeypot }<input type="text" name="perego_hp" tabIndex="-1" autoComplete="off" /></label>
				</div>
				<input type="hidden" name="comment_post_ID" value="0" />
				<input type="hidden" name="comment_parent" value="0" />
				<p className="comment-form__status" role="status" aria-live="polite"></p>
				<button type="submit" className="btn btn--accent comment-form__submit">{ SEED.submit }</button>
			</form>
		</div>
	);
}

/**
 * @param {Object}        props
 * @param {string}        props.title    Count heading.
 * @param {Array<Object>} props.comments Sample comments.
 */
export function JournalCommentsSkeleton( { title = SEED.title, comments = SEED_COMMENTS } ) {
	return (
		<section className="comments">
			<h2 className="comments__title">{ title }</h2>
			<ul className="comment-list">
				{ comments.map( ( comment ) => <CommentItem key={ comment.id } comment={ comment } /> ) }
			</ul>
			<CommentForm />
		</section>
	);
}

<?php
defined('PEPBAN_VERSION') || die;

function pepban_blog_posts(): array {
	return [
		[
			'slug'        => 'chargeback-ratio-payment-processor-account-terminated',
			'title'       => 'How Chargebacks Get Your Payment Processor Account Terminated',
			'date'        => 'July 26, 2026',
			'date_iso'    => '2026-07-26',
			'author'      => 'PepBan Team',
			'meta_desc'   => 'Payment processors terminate merchant accounts when chargebacks exceed 1%. Learn how the threshold works, what triggers a review, and how to protect your account.',
			'excerpt'     => 'Most merchants don\'t know they\'re in trouble until it\'s too late. Here\'s exactly how chargeback ratios work — and the point at which your processor will shut you down.',
			'content'     => <<<'HTML'
<p>Losing a payment processor account is one of the worst things that can happen to an e-commerce business. It doesn't happen with a warning email and a grace period. It typically happens with a sudden account termination notice, frozen funds, and a 180-day hold on your reserve. And it often happens to merchants who didn't realize they were anywhere near the threshold.</p>

<p>Understanding how chargeback ratios work — and what payment processors actually do with them — is the first step to making sure you never find out the hard way.</p>

<h2>The 1% Rule That Almost No One Knows About</h2>

<p>Every major card network publishes a chargeback threshold. Exceed it and you enter a monitoring program. Stay there and your acquiring bank terminates your account.</p>

<p>Visa's threshold is <strong>1% of monthly transactions</strong> by count, combined with at least 100 chargebacks in a month. Mastercard's threshold is <strong>1% by count</strong> with at least 100 chargebacks, or 1.5% with at least 1,000. American Express has its own program with similar triggers.</p>

<p>These numbers sound generous until you do the math. If you process 500 orders a month and receive 6 chargebacks, you're at 1.2% — already in violation of Visa's threshold. For a mid-sized peptide store doing decent volume, it takes a surprisingly small number of fraudulent customers to create a serious problem.</p>

<h2>How the Monitoring Programs Work</h2>

<p>When you exceed the threshold, you don't immediately lose your account. You enter a monitoring program — Visa's is called the Visa Dispute Monitoring Program (VDMP), Mastercard's is the Excessive Chargeback Merchant (ECM) program. These programs have two tiers:</p>

<p><strong>Standard / Early Warning</strong> — You're flagged, your acquiring bank is notified, and you typically receive a warning. No fines yet, but the clock starts.</p>

<p><strong>High-Risk / Excessive</strong> — For Visa, this triggers at 2% chargeback ratio with 1,000+ chargebacks. For Mastercard, it's 3%+ with 1,000+ chargebacks. At this level, monthly fines kick in — typically $25,000 per month from Visa, escalating over time.</p>

<p>The longer you stay in the program, the higher the fines and the more pressure your acquiring bank faces to terminate the relationship. Most acquirers will drop you before the fines get serious — termination protects them, even if it doesn't protect you.</p>

<h2>Your Acquiring Bank's Perspective</h2>

<p>Payment processors and their acquiring banks are liable for unresolved chargebacks. When a merchant with high chargebacks can't cover them — because they've spent the revenue, or they've gone out of business — the acquirer eats the loss.</p>

<p>This is why acquiring banks monitor chargeback ratios so closely and act quickly when thresholds are exceeded. It's not personal. They're protecting their own exposure. The moment you become more liability than revenue to them, you're gone.</p>

<p>For high-risk industries — and peptide stores are explicitly high-risk with most processors — acquirers maintain tighter internal thresholds than the card networks require. Many will begin conversations with merchants at 0.5% and terminate at 0.75%, well before the official 1% card network threshold is reached.</p>

<h2>What Account Termination Actually Looks Like</h2>

<p>Most merchants imagine termination as a process with notice periods, appeals, and transitions. In practice it often looks like this:</p>

<ul>
  <li>An email or letter arrives stating the merchant agreement is being terminated, effective immediately or within 30 days</li>
  <li>New transactions stop processing immediately or on the termination date</li>
  <li>A reserve is held — typically 5–10% of monthly volume for 90–180 days — to cover chargebacks that arrive after termination</li>
  <li>Funds in the settlement account are frozen pending review</li>
  <li>The merchant has no meaningful recourse to reverse the decision</li>
</ul>

<p>If you rely on that payment processor for your business, termination means you cannot accept card payments until you establish a new merchant account — a process that can take weeks or months, and is significantly harder once you've been terminated.</p>

<h2>The Window You Don't Know You Have</h2>

<p>Here's what makes chargeback ratios particularly dangerous: they're calculated on a monthly basis, but chargebacks arrive on a delay. A customer who makes a fraudulent purchase in January might not file the chargeback until March. That March chargeback counts against your March ratio — even though the transaction happened two months ago.</p>

<p>This lag means your current month's ratio reflects fraud that happened weeks or months earlier. By the time you see a problem in your ratio, the orders causing it have long since shipped. The only effective strategy is to stop fraudulent orders before they're placed — not to react after the chargeback arrives.</p>

<h2>What Actually Prevents Chargebacks</h2>

<p>Most chargeback prevention advice focuses on dispute responses — how to win a chargeback after it's been filed. That's useful, but it doesn't reduce your ratio in a meaningful way. Winning a chargeback dispute is better than losing one, but the chargeback still counted against your threshold when it was filed.</p>

<p>The only thing that meaningfully reduces chargeback ratios is preventing fraudulent orders from being placed in the first place. That means:</p>

<ul>
  <li>Blocking known fraudsters before checkout — by email, phone, IP, and billing address</li>
  <li>Sharing fraud data with other stores in your industry so known bad actors can't simply move to the next target</li>
  <li>Monitoring for red flags on new large orders from unverified customers</li>
  <li>Keeping a watch list for customers showing early warning signs before they escalate to a chargeback</li>
</ul>

<p>For peptide stores specifically, the fraud risk is concentrated enough that a shared blacklist makes a substantial difference. A fraudster who has hit five other stores in your industry is already in the database — and won't get through your checkout.</p>

<h2>How Many Chargebacks Can You Actually Afford?</h2>

<p>Run the numbers on your own store. Take your monthly order count and multiply by 0.009 (0.9%). That's your safe ceiling — the number of chargebacks per month you can absorb before you're at the threshold.</p>

<p>For a store processing 300 orders a month, that's 2–3 chargebacks. For 1,000 orders, it's 9. Every fraudulent order that ships and results in a chargeback eats into that ceiling. When you hit it, the consequences are not proportionate to the violation.</p>

<div class="pb-blog-cta">
  <h3>Keep Your Chargeback Ratio Under Control</h3>
  <p>Block known fraudsters before they order. Join the PepBan network and stop chargebacks before they happen.</p>
  <a href="/signup" class="pb-btn pb-btn-primary">Get Protected Now &rarr;</a>
</div>
HTML,
		],
		[
			'slug'        => 'match-list-terminated-merchant-account',
			'title'       => 'The MATCH List: Why Losing Your Payment Processor Can Follow You for Years',
			'date'        => 'July 26, 2026',
			'date_iso'    => '2026-07-26',
			'author'      => 'PepBan Team',
			'meta_desc'   => 'Getting added to the MATCH list after a merchant account termination can make it nearly impossible to get a new processor for 5 years. Here\'s what it means and how to avoid it.',
			'excerpt'     => 'Most merchants have never heard of the MATCH list — until they\'re on it. Once you\'re added, getting payment processing becomes extremely difficult for up to five years.',
			'content'     => <<<'HTML'
<p>When a payment processor terminates a merchant account due to excessive chargebacks or fraud, the consequences don't end with the termination. In most cases, the merchant is added to the MATCH list — a database that follows them across the entire payment industry for up to five years.</p>

<p>If you've never heard of the MATCH list, you're not alone. Most merchants don't know it exists until they're on it and suddenly can't open a new merchant account anywhere.</p>

<h2>What the MATCH List Is</h2>

<p>MATCH stands for Member Alert to Control High-Risk Merchants. It's a Mastercard-operated database that acquiring banks and payment processors are required to check before approving a new merchant account. When a payment processor terminates a merchant for certain reasons — including excessive chargebacks, fraud, or violation of the merchant agreement — they are <em>required</em> to add that merchant to MATCH within five days.</p>

<p>The listing includes the merchant's name, business name, address, principal owners' names, and the reason code for the termination. It stays on file for five years.</p>

<p>Every acquiring bank and most payment processors run new applicants through MATCH as part of their underwriting process. A MATCH hit is typically an automatic rejection. Some processors will work with MATCH-listed merchants at significantly higher rates and reserves, but many won't work with them at all.</p>

<h2>What Gets You Added</h2>

<p>Mastercard defines specific reason codes for MATCH listings. The most common for e-commerce merchants are:</p>

<ul>
  <li><strong>Reason Code 4 — Excessive Chargebacks:</strong> Monthly chargeback ratio exceeds 1% for two or more consecutive months. This is the most common reason for e-commerce merchants.</li>
  <li><strong>Reason Code 5 — Excessive Fraud:</strong> Monthly fraud-to-sales ratio exceeds 8% by dollar volume.</li>
  <li><strong>Reason Code 7 — Fraud Conviction:</strong> A principal of the business has been convicted of fraud.</li>
  <li><strong>Reason Code 8 — Mastercard Questionable Merchant Audit Program:</strong> The merchant has been flagged under Mastercard's own audit process.</li>
  <li><strong>Reason Code 12 — PCI-DSS Non-Compliance:</strong> The merchant is not compliant with payment card industry data security standards.</li>
</ul>

<p>For the average peptide store, Reason Code 4 is the primary risk. Once your chargeback ratio exceeds 1% for two consecutive months, your acquirer is required to report you when they terminate the account.</p>

<h2>What Happens After Termination</h2>

<p>The sequence typically goes like this:</p>

<ol>
  <li>Chargeback ratio exceeds threshold → you enter a monitoring program</li>
  <li>Ratio stays elevated → acquirer terminates the merchant agreement</li>
  <li>Acquirer adds you to MATCH within 5 business days of termination</li>
  <li>Reserve funds held for 90–180 days while outstanding chargebacks clear</li>
  <li>You attempt to open a new merchant account — and get rejected because of the MATCH listing</li>
</ol>

<p>The reserve hold compounds the problem. If 10% of your monthly volume is held for six months, you may not have the capital to keep operating while you find a new processor. Many businesses don't survive this gap.</p>

<h2>Getting Off the MATCH List</h2>

<p>There is no formal appeals process for MATCH listings. You cannot pay to be removed, and you cannot dispute your way off the list the way you would a credit report error.</p>

<p>Your only options are:</p>

<p><strong>Wait it out.</strong> Listings expire after five years. This is the guaranteed path, but five years without mainstream payment processing is not viable for most businesses.</p>

<p><strong>Dispute with the listing processor.</strong> If you believe you were incorrectly added — wrong reason code, factual errors — you can contact the processor that added you and request a correction. They are required to correct genuine errors. But if the chargeback ratio was genuinely elevated, there's nothing to dispute.</p>

<p><strong>Find a high-risk processor who will work with MATCH merchants.</strong> Some processors specialize in high-risk accounts and will onboard MATCH-listed merchants. Expect significantly higher processing fees (3–5%+ instead of 2–3%), higher rolling reserves (10–20% held for 90–180 days), volume caps, and more restrictive terms. The cost of processing goes up substantially — often enough to materially affect margins.</p>

<h2>What "High-Risk" Processing Actually Costs</h2>

<p>To understand why MATCH list prevention matters, it helps to see the real cost difference between standard and high-risk processing:</p>

<ul>
  <li><strong>Standard merchant account:</strong> 2.2–2.9% + $0.30 per transaction, no reserve, standard terms</li>
  <li><strong>High-risk account (pre-MATCH):</strong> 3–4% + $0.30, 5–10% rolling reserve, monthly fees, restricted chargeback ratio of 0.5%</li>
  <li><strong>High-risk account (post-MATCH):</strong> 4–6%+, 15–20% rolling reserve held for 180 days, volume caps, some processors decline entirely</li>
</ul>

<p>On $50,000 monthly revenue, the difference between standard and post-MATCH processing can be $1,500–2,000 per month in additional fees alone, before accounting for the capital tied up in reserves.</p>

<h2>The Fraud Connection</h2>

<p>What's frustrating about chargeback-driven MATCH listings is that many merchants didn't commit fraud — they were defrauded. The customer who filed the chargeback is the one who lied. But the card networks' dispute systems favor cardholders by design, and the downstream consequences fall on the merchant regardless of who was at fault.</p>

<p>This is why preventing fraudulent orders from being placed is so much more important than having good dispute response processes. A won dispute still generates a chargeback. A blocked order generates nothing — no chargeback, no ratio impact, no risk.</p>

<p>For peptide stores, where fraud rings specifically target the industry, the shared blacklist model is the most effective available defense. Fraudsters who have already hit other stores are in the network. They don't get to check out. They don't generate a chargeback. They don't move your ratio.</p>

<p>The five-year consequence of a MATCH listing makes chargeback prevention not just a financial consideration but an existential one. Most businesses can survive a bad month. Almost none survive five years of severely restricted payment processing.</p>

<div class="pb-blog-cta">
  <h3>Don't Find Out What the MATCH List Costs You</h3>
  <p>Block the fraudsters who drive chargebacks before they ever place an order.</p>
  <a href="/signup" class="pb-btn pb-btn-primary">Join PepBan Free &rarr;</a>
</div>
HTML,
		],
		[
			'slug'        => 'stop-chargeback-fraud-peptide-store',
			'title'       => 'How to Stop Chargeback Fraud on Your Peptide Store',
			'date'        => 'July 18, 2026',
			'date_iso'    => '2026-07-18',
			'author'      => 'PepBan Team',
			'meta_desc'   => 'Chargeback fraud is costing peptide stores thousands. Learn how a shared ban network stops the same scammers from hitting every store in the industry.',
			'excerpt'     => 'Chargeback fraud is one of the most expensive problems in the peptide industry. Here\'s how store owners are fighting back — together.',
			'content'     => <<<'HTML'
<p>If you run a peptide store, you already know the feeling. An order comes in, ships out, and a few weeks later you get the chargeback notice. The customer claims they never received it, or that it was "unauthorized." Your payment processor sides with them. You lose the product <em>and</em> the money — and collect a chargeback fee on top of it.</p>

<p>Do it enough times, and your payment processor puts you on a watchlist. Too many chargebacks and they terminate your account entirely. For a peptide store, losing payment processing isn't a minor inconvenience — it's an existential threat.</p>

<h2>Why Peptide Stores Are Targeted</h2>

<p>Fraud rings specifically target niche industries with high average order values, limited customer verification, and processors who are already jumpy about the product category. Peptide stores check every one of those boxes.</p>

<p>A single fraudster can easily place orders at five, ten, or twenty different peptide stores using different email addresses — or even the same one, betting that stores don't talk to each other. In most cases, that bet pays off. Each store is an island, handling its own fraud in isolation.</p>

<p>The scammer gets banned at store A on a Tuesday. By Wednesday they've placed orders at stores B, C, and D.</p>

<h2>The Real Cost of a Single Chargeback</h2>

<p>It's never just the product value. Here's the full math on a $200 peptide order that goes to chargeback:</p>

<ul>
  <li><strong>Product cost:</strong> $60–80 (goods + packaging + labor)</li>
  <li><strong>Shipping:</strong> $15–25</li>
  <li><strong>Chargeback fee:</strong> $20–100 depending on processor</li>
  <li><strong>Payment processor revenue share lost:</strong> $200</li>
  <li><strong>Time spent responding to dispute:</strong> 1–2 hours</li>
  <li><strong>Risk to account standing:</strong> Immeasurable</li>
</ul>

<p>A realistic $200 chargeback costs $300–400 all in. And the fraudsters know this. They also know most merchants won't fight back because the dispute process is painful and rarely goes in the merchant's favor for digital or chemical products.</p>

<h2>What Doesn't Work</h2>

<p>Most stores try a version of the same playbook:</p>

<ul>
  <li>Manual email screening (misses VPNs, throwaway addresses)</li>
  <li>Requiring ID verification (kills conversion for legitimate customers)</li>
  <li>Limiting orders from new accounts (the fraudster just waits)</li>
  <li>Adding a local blacklist of known bad actors</li>
</ul>

<p>The local blacklist approach is better than nothing, but it only works once — after you've already been hit. And it does nothing for the other 20 stores the same customer is about to defraud.</p>

<h2>What Actually Works: A Shared Network</h2>

<p>The answer isn't better individual defenses — it's collective intelligence. When every peptide store shares ban data in real time, a fraudster who hits one store gets blocked at every other store automatically. The economics flip: instead of one store eating the loss while the others remain exposed, the entire industry closes ranks.</p>

<p>This is the model PepBan is built on. When a store reports a customer to the PepBan network, that ban propagates instantly. The next time that email, IP address, phone number, or billing address tries to check out anywhere on the network, they're blocked before the order is ever placed.</p>

<p>No product ships. No chargeback is possible. No dispute to fight.</p>

<h2>How It Works in Practice</h2>

<p>PepBan is a WooCommerce plugin that connects your store to a centralized ban database shared across all member stores. Setup takes about five minutes:</p>

<ol>
  <li>Create a free account at pepban.com and get your API key</li>
  <li>Install the PepBan Client plugin on your WooCommerce store</li>
  <li>Paste your API key in the plugin settings</li>
</ol>

<p>From that point on, every checkout is checked against the shared database in real time. Banned customers are blocked before the order is created — not after. You can also report known bad actors directly from the WooCommerce order screen with one click, and they're immediately added to the network.</p>

<h2>Beyond Email: Multi-Vector Blocking</h2>

<p>Sophisticated fraudsters rotate email addresses. PepBan blocks on multiple vectors simultaneously:</p>

<ul>
  <li><strong>Email address</strong> — the primary identifier</li>
  <li><strong>Phone number</strong> — blocks the same person under a new email</li>
  <li><strong>IP address</strong> — blocks the same device or location</li>
  <li><strong>Billing address</strong> — blocks fraudulent shipping destinations</li>
  <li><strong>Email domain</strong> — block entire throwaway mail services</li>
</ul>

<p>Each layer makes it harder for a repeat offender to get through, even after changing their email.</p>

<h2>The Network Effect</h2>

<p>The value of a shared ban network compounds with size. Every new store that joins adds their ban history to the pool, making the network smarter for everyone. A fraudster who hit a store two years ago is still blocked today, across every member store.</p>

<p>This is why early adoption matters. Stores that join now are protected not just by their own reporting, but by the collective experience of every other member — including bans that predate their own store opening.</p>

<p>Chargeback fraud in the peptide industry isn't going away. But the stores working together are making it substantially less profitable for the people doing it.</p>

<div class="pb-blog-cta">
  <h3>Protect Your Store Today</h3>
  <p>Join the PepBan network and stop paying for other stores' fraudsters.</p>
  <a href="/signup" class="pb-btn pb-btn-primary">Create Free Account &rarr;</a>
</div>
HTML,
		],
		[
			'slug'        => 'shared-blacklist-peptide-stores',
			'title'       => 'The Shared Blacklist That Peptide Stores Are Using to Block Scammers',
			'date'        => 'July 21, 2026',
			'date_iso'    => '2026-07-21',
			'author'      => 'PepBan Team',
			'meta_desc'   => 'Peptide stores are sharing a real-time customer blacklist to stop the same scammers from hitting every store in the industry. Here\'s how it works.',
			'excerpt'     => 'One fraudster. Twenty stores. One shared blacklist changes the math entirely.',
			'content'     => <<<'HTML'
<p>There's a pattern that plays out constantly in the peptide industry. A customer places an order, the store ships it, and weeks later a chargeback arrives. The store bans the email address. Problem solved — for that store. Meanwhile, the same customer goes to the next peptide store and repeats the process.</p>

<p>No one talks to each other. Every store is learning the same painful lesson independently, over and over, from the same pool of bad actors.</p>

<p>A shared blacklist changes that equation entirely.</p>

<h2>The Problem with Siloed Fraud Data</h2>

<p>The peptide industry is small enough that most of its bad actors are well-known — to the people they've already defrauded. The problem isn't a shortage of data. It's that the data stays locked inside each individual store.</p>

<p>Store owner A knows that john.doe91@gmail.com is a fraudster. Store owner B, in a completely separate market, has no idea. So when john.doe91 shows up at store B — or uses john.doe.91@gmail.com at store C — there's nothing stopping the order.</p>

<p>Fraud rings know this. They maintain lists of stores they haven't hit yet. They rotate email addresses systematically. They use the same IP addresses and billing addresses with new accounts. Without cross-store communication, individual defenses are always playing catch-up.</p>

<h2>How a Shared Blacklist Works</h2>

<p>PepBan is a centralized ban database that every member store connects to through a WordPress plugin. When one store reports a customer, that ban is immediately available to every other store on the network.</p>

<p>At checkout, PepBan checks the customer's email, phone number, IP address, and billing address against the shared database in real time. If there's a match, the order is blocked before it's ever created. The customer sees a generic error message. No product ships. No chargeback is possible.</p>

<p>The reporting side is equally simple. From the WooCommerce order screen, there's a one-click button to report a customer to the network. You don't leave WordPress. You don't fill out a form. One click, and the customer is flagged for every other member store.</p>

<h2>What Gets Shared — and What Doesn't</h2>

<p>PepBan stores the minimum data needed to identify and block a fraudulent customer: email address, phone number, IP address, billing address, and the reason for the ban. Nothing beyond that leaves your store.</p>

<p>Your customer orders, purchase history, revenue, and product details stay entirely on your server. PepBan only receives the specific data you explicitly report. Member stores can see that a customer is banned and why — but they can't see which of your customers they are, what they ordered, or anything about your business.</p>

<h2>Local Control, Global Protection</h2>

<p>Not every ban should be global. PepBan gives stores full local control alongside network-wide protection.</p>

<p>Your <strong>local blacklist</strong> blocks customers only on your store — useful for customers you've had issues with that don't rise to the level of a network report, or for blocking by phone number, address, or IP without involving other stores.</p>

<p>Your <strong>local whitelist</strong> lets you allow a globally-banned customer through on your store specifically — for example, if you've verified their identity and are comfortable proceeding despite a network ban.</p>

<p>And <strong>watch notes</strong> let you flag a customer internally without banning them — a way to track patterns like repeated "non-delivery" claims before escalating to a ban.</p>

<h2>The Growing Value of the Network</h2>

<p>The power of a shared blacklist grows with its membership. Every store that joins adds their fraud history to the pool. A fraudster who hit the network two years ago is still blocked today — not just at the store that originally reported them, but at every store that has joined since.</p>

<p>For a store joining now, this means immediate protection from a database of known bad actors that predates their membership. You're not starting from zero. You inherit the collective experience of every member who came before you.</p>

<p>The network effect also works the other way: every new store that joins makes the network more valuable for existing members, because that store's ban history — and any new fraud they encounter — gets added to the pool.</p>

<h2>Automatic Protection, Zero Maintenance</h2>

<p>Once the plugin is installed, everything runs automatically. You don't need to manually update a list, download files, or check dashboards. Bans from other stores appear in real time. Your own reports go out in real time. The plugin updates itself through the WordPress admin.</p>

<p>The only time you need to actively do something is when you encounter a new fraudster and want to report them — and that takes one click.</p>

<p>For most stores, PepBan runs invisibly in the background, blocking dozens of orders per month from customers they never had to deal with because someone else already reported them first.</p>

<div class="pb-blog-cta">
  <h3>Join the Network</h3>
  <p>Stop learning from fraud the hard way. Join the stores already protected by the shared blacklist.</p>
  <a href="/signup" class="pb-btn pb-btn-primary">Get Protected Now &rarr;</a>
</div>
HTML,
		],
		[
			'slug'        => 'woocommerce-customer-blacklist-plugin',
			'title'       => 'WooCommerce Customer Blacklist: Block Repeat Offenders Before They Checkout',
			'date'        => 'July 23, 2026',
			'date_iso'    => '2026-07-23',
			'author'      => 'PepBan Team',
			'meta_desc'   => 'The PepBan WooCommerce plugin lets you block customers by email, phone, IP, and billing address — and share bans with other peptide stores in real time.',
			'excerpt'     => 'WooCommerce doesn\'t have built-in customer blocking. Here\'s how to add it — plus connect to a shared ban network across every peptide store.',
			'content'     => <<<'HTML'
<p>WooCommerce is built to help you sell. It doesn't have a built-in way to block specific customers from placing orders. For most industries, that's fine. For peptide stores dealing with repeat chargebacks and fraud rings, it's a significant gap.</p>

<p>This guide covers how to add customer blocking to WooCommerce — and how to connect it to a shared network so you're protected from fraudsters even before they've hit your store.</p>

<h2>What WooCommerce Doesn't Do Out of the Box</h2>

<p>WooCommerce lets you cancel and refund orders, but there's nothing to stop the same customer from placing another one. Even if you delete their account, they can checkout as a guest. Even if you block their IP at the server level, they can use a VPN. Email-based blocking at the application layer is the only reliable first line of defense — and WooCommerce doesn't include it.</p>

<p>The typical workaround is to add customers to a "Do Not Ship" note or tag, then manually check orders against that list. It's tedious, unreliable, and doesn't scale.</p>

<h2>What You Actually Need</h2>

<p>Effective customer blocking for a WooCommerce store needs to work at checkout — before the order is placed, not after. And it needs to cover more than just email addresses, since repeat offenders routinely change them.</p>

<p>A complete solution checks:</p>

<ul>
  <li><strong>Email address</strong> — the most reliable single identifier</li>
  <li><strong>Phone number</strong> — catches re-registrations with new email</li>
  <li><strong>IP address</strong> — catches the same device under a new account</li>
  <li><strong>Billing address</strong> — catches the same physical location</li>
  <li><strong>Email domain</strong> — lets you block entire throwaway mail services</li>
</ul>

<p>Blocking on any single vector alone is insufficient. A determined fraudster changes email addresses constantly. Combined blocking across all vectors makes it substantially harder to slip through.</p>

<h2>PepBan Client: How It Works</h2>

<p>PepBan Client is a WooCommerce plugin that adds a local blacklist to your store and connects it to a shared network across all member stores.</p>

<p>Installation takes about five minutes:</p>

<ol>
  <li>Sign up at pepban.com and get your API key</li>
  <li>Download the plugin ZIP from your portal</li>
  <li>Upload to WordPress via Plugins → Add New → Upload Plugin</li>
  <li>Activate and enter your API key in PepBan → Settings</li>
</ol>

<p>Once configured, every checkout is automatically checked against both your local blacklist and the shared network database. Blocked customers see a configurable error message and cannot complete the order.</p>

<h2>Local Blacklist vs. Network Bans</h2>

<p>PepBan gives you two layers of protection:</p>

<p><strong>Local blacklist</strong> — customers you ban on your store only. These don't get reported to the network unless you explicitly click "Report to PepBan." Useful for customers you've had minor issues with, or for blocking by phone number or address without a full network report.</p>

<p><strong>Network bans</strong> — bans reported by any member store. These are checked at checkout automatically. When a customer is blocked by a network ban, they're blocked on your store even if you've never encountered them before.</p>

<p>This is the core value of the network: you benefit from every other store's fraud experience, and they benefit from yours.</p>

<h2>Reporting a Fraudulent Customer</h2>

<p>When you identify a fraudster — whether through a chargeback, a suspicious order pattern, or a direct dispute — you can report them to the network directly from the WooCommerce order screen.</p>

<p>There's a "Report to PepBan" button on every order. Clicking it opens a form where you can confirm the email and add a reason. The report goes to the network immediately, and the customer is blocked at every member store in real time.</p>

<p>You can also report in bulk via CSV import if you have an existing list of known bad actors you want to add all at once.</p>

<h2>Customer Lookup</h2>

<p>Before processing a high-value order or approving a return, you can look up any customer from PepBan → Customer Lookup. Enter their email and you'll instantly see:</p>

<ul>
  <li>Their network ban status and reason</li>
  <li>How many stores have reported them and the total report count</li>
  <li>Any watch notes your staff has added</li>
  <li>Their local blacklist status on your store</li>
</ul>

<p>From the same screen, you can add a watch note, add them to your local blacklist, or remove a blacklist entry — without navigating to a separate page.</p>

<h2>Watch Notes: Flag Without Banning</h2>

<p>Sometimes a customer doesn't warrant a ban yet — but you want to keep an eye on them. Watch notes let you add an internal flag to any customer by email.</p>

<p>The note shows as an orange warning banner on every order from that customer in your WooCommerce admin. Staff can see the flag without having to remember or look up individual order history. If the pattern escalates, converting a watch note to a ban (local or network) is one click.</p>

<h2>Automatic Updates</h2>

<p>PepBan Client updates automatically through the WordPress dashboard, the same way any other plugin does. New versions appear in the standard Plugins update list. You don't need to download anything or manage plugin files manually.</p>

<div class="pb-blog-cta">
  <h3>Add Customer Blocking to Your Store</h3>
  <p>Five minutes to install. Protection from the first checkout.</p>
  <a href="/signup" class="pb-btn pb-btn-primary">Start Free &rarr;</a>
</div>
HTML,
		],
		[
			'slug'        => 'peptide-customer-chargeback-red-flags',
			'title'       => '5 Red Flags That a Peptide Customer Is About to Chargeback',
			'date'        => 'July 25, 2026',
			'date_iso'    => '2026-07-25',
			'author'      => 'PepBan Team',
			'meta_desc'   => 'Learn the five warning signs that a peptide customer is planning a chargeback — and what you can do to stop it before the order ships.',
			'excerpt'     => 'Experienced peptide store owners can often spot a chargeback before it happens. Here are the five biggest warning signs — and what to do about each one.',
			'content'     => <<<'HTML'
<p>Chargeback fraud isn't random. The customers doing it follow patterns — patterns that experienced peptide store owners recognize. Knowing what to look for won't catch every fraudulent order, but it will stop the most obvious ones before you've shipped anything.</p>

<p>Here are the five most reliable red flags, and what to do when you see them.</p>

<h2>1. The Email Address Looks Generated</h2>

<p>Real customers tend to use their name, initials, or a recognizable handle. Fraudsters either use obviously fake addresses (john12847392@gmail.com) or sophisticated variations designed to look real but bypass email-based bans (j.o.h.n.doe@gmail.com, john+peptides@gmail.com).</p>

<p>A few specific patterns to watch for:</p>

<ul>
  <li>Long strings of random numbers after the name</li>
  <li>Dots inserted throughout the name part (Gmail treats these as the same address, but many stores track them separately)</li>
  <li>Plus-tags on Gmail addresses (john+shop1@gmail.com, john+shop2@gmail.com)</li>
  <li>Disposable mail services (mailinator, guerrillamail, tempmail, yopmail)</li>
</ul>

<p><strong>What to do:</strong> Block known disposable mail domains at the domain level. For suspicious-looking emails you can't confirm as disposable, flag the order for manual review before shipping. PepBan's domain blocking feature lets you block entire throwaway mail services with one entry.</p>

<h2>2. The Billing Address and Shipping Address Don't Match — and the Shipping Address Is Unusual</h2>

<p>A billing address in one state and a shipping address in another isn't automatically suspicious — people buy gifts for others. But specific patterns raise the risk considerably:</p>

<ul>
  <li>Shipping to a freight forwarder, parcel consolidation service, or mail forwarding address</li>
  <li>Shipping to a hotel or motel address</li>
  <li>Shipping to a city or ZIP known for high fraud rates</li>
  <li>Billing address that doesn't match the card's known location (AVS mismatch)</li>
</ul>

<p>Freight forwarder addresses are particularly common in peptide fraud because they add a layer of distance between the fraudster and the product. By the time the chargeback hits, the package has been forwarded internationally and recovery is essentially impossible.</p>

<p><strong>What to do:</strong> Require billing and shipping address to match on first orders, or flag freight forwarder addresses for manual review. Add known problematic addresses to your PepBan local blacklist so they're blocked automatically on future orders.</p>

<h2>3. The Order Is Unusually Large for a New Customer</h2>

<p>Legitimate new customers typically place a smaller first order to test product quality and shipping before committing to larger purchases. Fraudsters do the opposite — they maximize the value of each fraudulent order because they're never paying for it.</p>

<p>A new customer placing an order that's 3–5x your average order value, especially of high-value peptides, is worth scrutinizing. This is especially true if it's the maximum quantity of specific high-demand products.</p>

<p><strong>What to do:</strong> Set a soft threshold for new customer order values that triggers manual review. For orders above the threshold, verify the customer's identity before shipping, or limit first orders to a lower cap. Consider requiring a phone number on large orders, which also helps with PepBan's phone-based blocking if they turn out to be a fraudster.</p>

<h2>4. They Contact Support Immediately After Ordering</h2>

<p>Experienced fraud rings have refined their tactics to maximize the appearance of legitimacy. One common move is to contact the store's support immediately after placing the order to create a paper trail — "confirming" their address, asking about tracking, or flagging a non-existent issue with the order.</p>

<p>The goal is to make the eventual "I never received it" chargeback more credible. If they've already been in contact with you about the order, they can claim they tried to resolve it before going to their bank.</p>

<p><strong>What to do:</strong> Note any unusually proactive support contact on large new-customer orders. Cross-reference the email with PepBan before responding — if they're on the network ban list, don't ship the order. If they're new to you but the pattern feels off, check the order details carefully before proceeding.</p>

<h2>5. Multiple Failed Payment Attempts Before Success</h2>

<p>Legitimate customers almost always get their payment right on the first or second try. Multiple failed attempts — especially with different cards — suggest either a compromised card being tested or someone trying multiple stolen cards until one goes through.</p>

<p>This is harder to catch at the application level since payment processors handle card validation, but WooCommerce logs these attempts and some payment gateways flag high failure rates.</p>

<p><strong>What to do:</strong> If your payment gateway supports it, set a maximum number of failed payment attempts per session before blocking the checkout. Flag orders that come through after multiple failed attempts for manual review. Report confirmed card fraud cases to PepBan — the email and IP associated with the fraud are useful network data even if the card itself can't be blocked.</p>

<h2>When You've Been Hit: Report It</h2>

<p>Even with all the right checks in place, some fraudulent orders will get through. When they do, reporting the customer to the PepBan network is the most valuable thing you can do — not just to protect yourself from a repeat, but to protect every other store on the network from their first encounter with this customer.</p>

<p>The industry's best defense against chargeback fraud is shared information. Every report makes the network stronger for everyone.</p>

<div class="pb-blog-cta">
  <h3>Stop Fraud Before It Ships</h3>
  <p>Block known fraudsters at checkout automatically with PepBan's shared network.</p>
  <a href="/signup" class="pb-btn pb-btn-primary">Join the Network Free &rarr;</a>
</div>
HTML,
		],
	];
}

function pepban_blog_post_by_slug(string $slug): ?array {
	foreach (pepban_blog_posts() as $post) {
		if ($post['slug'] === $slug) return $post;
	}
	return null;
}

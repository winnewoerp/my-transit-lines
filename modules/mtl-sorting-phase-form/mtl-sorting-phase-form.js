const title_input = document.getElementById('proposal-title-input');
const url_input = document.getElementById('proposal-url-input');
const url_link = document.getElementById('proposal-url-link');
const id_input = document.getElementById('proposal-id-input');

if (title_input && url_input && url_link && id_input) {
	let last_handled_input = 0;
	let last_received_input = 0;

	title_input.addEventListener('input', async () => {
		if (title_input.value.length < 3) {
			title_input.list.replaceChildren();
			url_input.value = "";
			url_link.href = "";
			id_input.value = "";
			return;
		}

		const nowTime = Date.now();
		const minimumAgeAgoDate = new Date(nowTime - parseInt(form_variables.minimum_age_days) * 24 * 60 * 60 * 1000); // This is precise enough, as it's not about a really strict date check
		const url = form_variables.rest_base + "wp/v2/proposals?search_columns=post_title" + (form_variables.search_tag ? ("&tags=" + form_variables.search_tag) : "") + "&search=" + title_input.value.replaceAll('+', '&plus;') + "&before=" + minimumAgeAgoDate.toISOString();

		last_received_input = nowTime;
		await delay(Math.max(last_handled_input - nowTime + 1000, 0));
		if (nowTime < last_received_input)
			return;
		last_handled_input = nowTime;

		try {
			const response = await fetch(url);
			if (!response.ok) {
				throw new Error(`Response status: ${response.status}`);
			}

			let json = await response.json();
			const matching_proposal = json.find(proposal => { return proposal.title.rendered == title_input.value; });

			if (matching_proposal) {
				url_input.value = matching_proposal.link;
				url_link.href = matching_proposal.link;
				id_input.value = matching_proposal.id;
			} else {
				url_input.value = "";
				url_link.href = "";
				id_input.value = "";
			}

			title_input.list.replaceChildren(...json.map(proposal => {
				const option = document.createElement('option');
				option.value = proposal.title.rendered;
				return option;
			}));
		} catch (error) {
			console.error(error.message);
		}
	});
}
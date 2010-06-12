{embed="partials/header"}
{exp:weblog:entries weblog="spaces" limit="1"}
		<h3>{title}</h3>
		
		<p>{description}</p>
	{categories}

<a href="{path='space/list'}">{category_name}</a>

{/categories}
	
	
		<ul>
		<li><strong>Number of Seats</strong>{number_of_seats}</li>
		<li><strong>Size</strong>{size}</li>
		<li><strong>Address</strong>{address}</li>
		<li><strong>City</strong>{city}</li>
		<li><strong>State</strong>{state}</li>
		<li><strong>Zip</strong>{zip}</li>
		<li><strong>Contact Person</strong>{contact_person}</li>
		<li><strong>Contact Email</strong>{contactemail}</li>
		<li><strong>Website</strong>{website}</li>
		<li><strong>Cost</strong>{cost}</li>
		<li><strong>Parking</strong>{parking}</li>
		<li><strong>Public Transportation</strong>{public_transportation}</li>
		<li><strong>Floor Dimensions</strong>{floor_dimensions}</li>
		<li><strong>Ceiling Height</strong>{ceiling_height}</li>
		<li><strong>Floor Type</strong>{floor_type}</li>
		<li><strong>Food and Alcohol allowed?</strong>{food_alcohol}</li>
		<li><strong>Latitude</strong>{latitude}</li>
		<li><strong>Longitude</strong>{longitude}</li>
		
		</ul>
		
		
		
		
						{/exp:weblog:entries}
						{embed="partials/footer"}

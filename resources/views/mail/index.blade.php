<!DOCTYPE html>
<html lang="en">
	<head><base href="../../"/>
		<title>Codelink ERP</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<link rel="canonical" href="https://preview.keenthemes.com/metronic8" />
		<link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />

		<link href="{{ asset('plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
		<link href="{{ asset('style.bundle.css') }}" rel="stylesheet" type="text/css" />
	</head>
	<body id="kt_body" class="app-blank">
		<script>var defaultThemeMode = "light"; var themeMode; if ( document.documentElement ) { if ( document.documentElement.hasAttribute("data-bs-theme-mode")) { themeMode = document.documentElement.getAttribute("data-bs-theme-mode"); } else { if ( localStorage.getItem("data-bs-theme") !== null ) { themeMode = localStorage.getItem("data-bs-theme"); } else { themeMode = defaultThemeMode; } } if (themeMode === "system") { themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"; } document.documentElement.setAttribute("data-bs-theme", themeMode); }</script>
		<div class="d-flex flex-column flex-root" id="kt_app_root">
			<div class="d-flex flex-column flex-column-fluid">
				<div class="scroll-y flex-column-fluid px-10 py-10" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_app_header_nav" data-kt-scroll-offset="5px" data-kt-scroll-save-state="true" style="background-color:#d5d9e24a; --kt-scrollbar-color: #d9d0cc; --kt-scrollbar-hover-color: #d9d0cc">
					<style>html,body { padding:0; margin:0; font-family: Inter, Helvetica, "sans-serif"; } a:hover { color: #009ef7; }</style>
					<div id="#kt_app_body_content" style="font-family:Arial,Helvetica,sans-serif; line-height: 1.5; min-height: 100%; font-weight: normal; font-size: 15px; color: #2F3044; margin:0; padding:0; width:100%;">
						<div style="background-color:#ffffff; padding: 45px 0 34px 0; margin:40px auto; max-width: 600px;">
							<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" height="auto" style="border-collapse:collapse">
								<tbody>
									<tr>
										<td align="center" valign="center" style="text-align:center; padding-bottom: 10px">
											<div style="margin-bottom:55px; text-align:left">
												<div style="margin:-10px 60px 54px 60px; text-align:center;">
													<a href="https://erp.codelinkinfotech.com" rel="noopener" target="_blank">
														<img alt="Logo" src="{{ asset('public/email/codelink.png') }}" style="height: 35px" />
													</a>
												</div>
											</div>
										</td>
									</tr>
                                    @yield('content')
                                    <tr>
										<td align="center" valign="center" style="font-size: 13px; text-align:center; padding: 0 10px 10px 10px; font-weight: 500; color: #A1A5B7; font-family:Arial,Helvetica,sans-serif">
											<p style="margin-bottom:4px">
											<a href="tel:+91 9313115674" rel="noopener" target="_blank" style="font-weight: 600">+91 9313115674</a> |
											<a href="https://codelinkinfotech.com" rel="noopener" target="_blank" style="font-weight: 600">www.codelinkinfotech.com</a>.</p>
										</td>
									</tr>
									<tr>
										<td align="center" valign="center" style="text-align:center; padding-bottom: 20px;">
											<a href="https://www.linkedin.com/company/codelink-infotech/" style="margin-right:10px; text-decoration: none;">
												<img alt="Logo" src="{{ asset('public/email/linkedin.png') }}" style="width: 19px;height: 21px;" />
											</a>
											<a href="https://www.instagram.com/codelinkinfotech/" style="margin-right:10px; text-decoration: none;">
												<img alt="Logo" src="{{ asset('public/email/Instagram_icon.png') }}" style="width: 19px;height: 21px;"/>
											</a>
											<a href="https://www.facebook.com/codelinkinfotech" style="margin-right:10px; text-decoration: none;">
												<img alt="Logo" src="{{ asset('public/email/fb.jpeg') }}" style="width: 19px;height: 21px;" />
											</a>
										</td>
									</tr>
									<tr>
										<td align="center" valign="center" style="font-size: 13px; padding:0 15px; text-align:center; font-weight: 500; color: #A1A5B7;font-family:Arial,Helvetica,sans-serif">
											<p>&copy; Copyright Codelink Infotech
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script src="{{ asset('plugins.bundle.js') }}"></script>
		<script src="{{ asset('scripts.bundle.js') }}"></script>
	</body>
</html>

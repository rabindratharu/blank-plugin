import banner from "../../images/banner.svg";

import { __ } from "@wordpress/i18n";

const Content = () => {
	return (
		<>
			<section className="bg-[#deeff7] py-11 md:py-20 px-4 rounded-sm">
				<div className="banner flex flex-col justify-between items-center gap-y-6 md:gap-y-10">
					<img className="banner_image w-100" src={banner} alt="" />
					<div className="banner__content flex flex-col justify-between items-center w-full gap-y-4 md:gap-y-6">
						<h3 className="banner__title font-bold font-mono">
							{__("Getting Started To Blank plugn", "blank-plugin")}
						</h3>
						<h5 className="banner__sub-title text-2xl">
							{__("Getting Started To Blank plugn", "blank-plugin")}
						</h5>
						<div className="banner__buttons flex flex-wrap justify-center items-center gap-x-1.5 mt-11 md:mt-14">
							<a
								href="#"
								className="banner__button banner__button--primary  bg-blue-500 hover:bg-blue-700"
							>
								{__("View Demo", "blank-plugin")}
							</a>
							<a
								href="#"
								className="banner__button banner__button--secondary"
							>
								{__("Buy Now", "blank-plugin")}
							</a>
						</div>
					</div>
				</div>
			</section>
		</>
	);
};

export default Content;

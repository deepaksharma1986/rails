require 'rails_helper'

RSpec.describe ContactsController, type: :controller do

  describe "POST #create" do

    context "with valid attributes" do
      it "saves the new contact in the database" do
        expect{ post :create, contact: attributes_for(:contact)
        }.to change(Contact, :count).by(1)
      end

      it "redirects to contacts#show" do
        post :create,contact: attributes_for(:contact)
        expect(response).to redirect_to contact_path(assigns[:contact])
      end
    end
  end

end